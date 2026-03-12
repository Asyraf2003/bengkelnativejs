<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Shared\DTO\Result;
use App\Core\Inventory\Costing\ProductInventoryCosting;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Inventory\ProductInventory\ProductInventory;
use App\Core\Procurement\Supplier\Supplier;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Core\Procurement\SupplierReceipt\SupplierReceipt;
use App\Core\Procurement\SupplierReceipt\SupplierReceiptLine;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Inventory\InventoryMovementWriterPort;
use App\Ports\Out\Inventory\ProductInventoryCostingReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingWriterPort;
use App\Ports\Out\Inventory\ProductInventoryReaderPort;
use App\Ports\Out\Inventory\ProductInventoryWriterPort;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;
use App\Ports\Out\Procurement\SupplierReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptWriterPort;
use App\Ports\Out\Procurement\SupplierWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;
use Throwable;

final class CreateSupplierInvoiceFlowHandler
{
    public function __construct(
        private readonly SupplierReaderPort $suppliers,
        private readonly SupplierWriterPort $supplierWriter,
        private readonly SupplierInvoiceWriterPort $supplierInvoices,
        private readonly SupplierReceiptWriterPort $supplierReceipts,
        private readonly SupplierPaymentWriterPort $supplierPayments,
        private readonly ProductReaderPort $products,
        private readonly InventoryMovementWriterPort $inventoryMovements,
        private readonly ProductInventoryReaderPort $productInventories,
        private readonly ProductInventoryWriterPort $productInventoryWriter,
        private readonly ProductInventoryCostingReaderPort $productInventoryCostings,
        private readonly ProductInventoryCostingWriterPort $productInventoryCostingWriter,
        private readonly TransactionManagerPort $transactions,
        private readonly UuidPort $uuid,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function handle(
        string $namaPtPengirim,
        string $tanggalPengiriman,
        array $lines,
        bool $autoReceive = true,
        ?string $tanggalTerima = null,
    ): Result {
        try {
            $shipmentDate = $this->parseTanggal($tanggalPengiriman, 'Tanggal pengiriman wajib berupa tanggal yang valid dengan format Y-m-d.');
            $normalizedNamaPtPengirim = $this->normalizeNamaPtPengirim($namaPtPengirim);
            $invoiceLines = $this->buildInvoiceLines($lines);
            $receiptDate = $autoReceive
                ? $this->resolveReceiptDate($shipmentDate, $tanggalTerima)
                : null;
        } catch (DomainException $e) {
            return Result::failure(
                $e->getMessage(),
                ['supplier_invoice' => ['INVALID_SUPPLIER_INVOICE']]
            );
        }

        $transactionStarted = false;

        try {
            $this->transactions->begin();
            $transactionStarted = true;

            $supplier = $this->resolveSupplier($namaPtPengirim, $normalizedNamaPtPengirim);

            $supplierInvoice = SupplierInvoice::create(
                $this->uuid->generate(),
                $supplier->id(),
                $shipmentDate,
                $invoiceLines,
            );

            $this->supplierInvoices->create($supplierInvoice);

            $supplierPayment = SupplierPayment::create(
                $this->uuid->generate(),
                $supplierInvoice->id(),
                $supplierInvoice->grandTotalRupiah(),
                $shipmentDate,
                SupplierPayment::PROOF_STATUS_PENDING,
                null,
            );

            $this->supplierPayments->create($supplierPayment);

            $response = [
                'id' => $supplierInvoice->id(),
                'supplier_id' => $supplierInvoice->supplierId(),
                'nama_pt_pengirim' => $supplier->namaPtPengirim(),
                'tanggal_pengiriman' => $supplierInvoice->tanggalPengiriman()->format('Y-m-d'),
                'jatuh_tempo' => $supplierInvoice->jatuhTempo()->format('Y-m-d'),
                'grand_total_rupiah' => $supplierInvoice->grandTotalRupiah()->amount(),
                'auto_received' => false,
                'auto_settled' => true,
                'supplier_payment' => [
                    'id' => $supplierPayment->id(),
                    'supplier_invoice_id' => $supplierPayment->supplierInvoiceId(),
                    'amount_rupiah' => $supplierPayment->amountRupiah()->amount(),
                    'paid_at' => $supplierPayment->paidAt()->format('Y-m-d'),
                    'proof_status' => $supplierPayment->proofStatus(),
                    'proof_storage_path' => $supplierPayment->proofStoragePath(),
                ],
                'lines' => array_map(
                    static fn (SupplierInvoiceLine $line): array => [
                        'id' => $line->id(),
                        'product_id' => $line->productId(),
                        'qty_pcs' => $line->qtyPcs(),
                        'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                        'unit_cost_rupiah' => $line->unitCostRupiah()->amount(),
                    ],
                    $supplierInvoice->lines(),
                ),
            ];

            if ($autoReceive && $receiptDate !== null) {
                $receiptLines = $this->buildFullReceiptLines($supplierInvoice->lines());

                $supplierReceipt = SupplierReceipt::create(
                    $this->uuid->generate(),
                    $supplierInvoice->id(),
                    $receiptDate,
                    $receiptLines,
                );

                $movements = $this->buildStockInMovements(
                    $supplierInvoice->lines(),
                    $receiptLines,
                    $receiptDate,
                );

                $this->supplierReceipts->create($supplierReceipt);
                $this->inventoryMovements->createMany($movements);
                $this->applyInventoryCostingProjection($movements);
                $this->applyInventoryProjection($movements);

                $response['auto_received'] = true;
                $response['supplier_receipt'] = [
                    'id' => $supplierReceipt->id(),
                    'supplier_invoice_id' => $supplierReceipt->supplierInvoiceId(),
                    'tanggal_terima' => $supplierReceipt->tanggalTerima()->format('Y-m-d'),
                    'lines' => array_map(
                        static fn (SupplierReceiptLine $line): array => [
                            'id' => $line->id(),
                            'supplier_invoice_line_id' => $line->supplierInvoiceLineId(),
                            'qty_diterima' => $line->qtyDiterima(),
                        ],
                        $supplierReceipt->lines(),
                    ),
                ];
            }

            $this->transactions->commit();

            return Result::success(
                $response,
                'Supplier invoice berhasil dibuat.'
            );
        } catch (DomainException $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['supplier_invoice' => ['INVALID_SUPPLIER_INVOICE']]
            );
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     * @return list<SupplierInvoiceLine>
     */
    private function buildInvoiceLines(array $lines): array
    {
        if ($lines === []) {
            throw new DomainException('Supplier invoice minimal harus memiliki satu line.');
        }

        $invoiceLines = [];

        foreach ($lines as $line) {
            if (array_key_exists('product_id', $line) === false) {
                throw new DomainException('Product id pada supplier invoice line wajib ada.');
            }

            if (array_key_exists('qty_pcs', $line) === false) {
                throw new DomainException('Qty pcs pada supplier invoice line wajib ada.');
            }

            if (array_key_exists('line_total_rupiah', $line) === false) {
                throw new DomainException('Line total rupiah pada supplier invoice line wajib ada.');
            }

            $productId = trim((string) $line['product_id']);
            $qtyPcs = (int) $line['qty_pcs'];
            $lineTotalRupiah = (int) $line['line_total_rupiah'];

            if ($this->products->getById($productId) === null) {
                throw new DomainException('Product pada supplier invoice line tidak ditemukan.');
            }

            $invoiceLines[] = SupplierInvoiceLine::create(
                $this->uuid->generate(),
                $productId,
                $qtyPcs,
                Money::fromInt($lineTotalRupiah),
            );
        }

        return $invoiceLines;
    }

    private function resolveSupplier(
        string $namaPtPengirim,
        string $normalizedNamaPtPengirim,
    ): Supplier {
        $existingSupplier = $this->suppliers->getByNormalizedNamaPtPengirim($normalizedNamaPtPengirim);

        if ($existingSupplier !== null) {
            return $existingSupplier;
        }

        $supplier = Supplier::create(
            $this->uuid->generate(),
            $namaPtPengirim,
        );

        $this->supplierWriter->create($supplier);

        return $supplier;
    }

    /**
     * @param list<SupplierInvoiceLine> $invoiceLines
     * @return list<SupplierReceiptLine>
     */
    private function buildFullReceiptLines(array $invoiceLines): array
    {
        $receiptLines = [];

        foreach ($invoiceLines as $invoiceLine) {
            $receiptLines[] = SupplierReceiptLine::create(
                $this->uuid->generate(),
                $invoiceLine->id(),
                $invoiceLine->qtyPcs(),
            );
        }

        return $receiptLines;
    }

    /**
     * @param list<SupplierInvoiceLine> $invoiceLines
     * @param list<SupplierReceiptLine> $receiptLines
     * @return list<InventoryMovement>
     */
    private function buildStockInMovements(
        array $invoiceLines,
        array $receiptLines,
        DateTimeImmutable $receiptDate,
    ): array {
        $invoiceLinesById = [];

        foreach ($invoiceLines as $invoiceLine) {
            $invoiceLinesById[$invoiceLine->id()] = $invoiceLine;
        }

        $movements = [];

        foreach ($receiptLines as $receiptLine) {
            $invoiceLineId = $receiptLine->supplierInvoiceLineId();

            if (array_key_exists($invoiceLineId, $invoiceLinesById) === false) {
                throw new DomainException('Supplier invoice line tidak ditemukan untuk auto receive.');
            }

            $invoiceLine = $invoiceLinesById[$invoiceLineId];

            $movements[] = InventoryMovement::create(
                $this->uuid->generate(),
                $invoiceLine->productId(),
                'stock_in',
                'supplier_receipt_line',
                $receiptLine->id(),
                $receiptDate,
                $receiptLine->qtyDiterima(),
                $invoiceLine->unitCostRupiah(),
            );
        }

        return $movements;
    }

    /**
     * @param list<InventoryMovement> $movements
     */
    private function applyInventoryProjection(array $movements): void
    {
        /** @var array<string, ProductInventory> $inventoriesByProduct */
        $inventoriesByProduct = [];

        foreach ($movements as $movement) {
            $productId = $movement->productId();

            if (array_key_exists($productId, $inventoriesByProduct) === false) {
                $inventoriesByProduct[$productId] = $this->productInventories->getByProductId($productId)
                    ?? ProductInventory::create($productId, 0);
            }

            $inventoriesByProduct[$productId]->increase($movement->qtyDelta());
        }

        foreach ($inventoriesByProduct as $inventory) {
            $this->productInventoryWriter->upsert($inventory);
        }
    }

    /**
     * @param list<InventoryMovement> $movements
     */
    private function applyInventoryCostingProjection(array $movements): void
    {
        /** @var array<string, array{incoming_qty: int, incoming_total_cost_rupiah: int}> $incomingByProduct */
        $incomingByProduct = [];

        foreach ($movements as $movement) {
            if ($movement->movementType() !== 'stock_in') {
                continue;
            }

            $productId = $movement->productId();

            if (array_key_exists($productId, $incomingByProduct) === false) {
                $incomingByProduct[$productId] = [
                    'incoming_qty' => 0,
                    'incoming_total_cost_rupiah' => 0,
                ];
            }

            $incomingByProduct[$productId]['incoming_qty'] += $movement->qtyDelta();
            $incomingByProduct[$productId]['incoming_total_cost_rupiah'] += $movement->totalCostRupiah()->amount();
        }

        foreach ($incomingByProduct as $productId => $incoming) {
            $existingQtyOnHand = $this->productInventories->getByProductId($productId)?->qtyOnHand() ?? 0;

            $costing = $this->productInventoryCostings->getByProductId($productId)
                ?? ProductInventoryCosting::create(
                    $productId,
                    Money::zero(),
                    Money::zero(),
                );

            $costing->applyIncomingStock(
                $existingQtyOnHand,
                $incoming['incoming_qty'],
                Money::fromInt($incoming['incoming_total_cost_rupiah']),
            );

            $this->productInventoryCostingWriter->upsert($costing);
        }
    }

    private function resolveReceiptDate(
        DateTimeImmutable $shipmentDate,
        ?string $tanggalTerima,
    ): DateTimeImmutable {
        if ($tanggalTerima === null || trim($tanggalTerima) === '') {
            return $shipmentDate;
        }

        return $this->parseTanggal(
            $tanggalTerima,
            'Tanggal terima wajib berupa tanggal yang valid dengan format Y-m-d.'
        );
    }

    private function parseTanggal(
        string $tanggal,
        string $message,
    ): DateTimeImmutable {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', trim($tanggal));

        if ($parsed === false || $parsed->format('Y-m-d') !== trim($tanggal)) {
            throw new DomainException($message);
        }

        return $parsed;
    }

    private function normalizeNamaPtPengirim(string $namaPtPengirim): string
    {
        $normalized = trim($namaPtPengirim);

        if ($normalized === '') {
            throw new DomainException('Nama PT pengirim wajib ada.');
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return mb_strtolower($normalized);
    }
}
