<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Inventory\Services\InventoryProjectionService;
use App\Application\Procurement\Services\{SupplierService, SupplierInvoiceFactory};
use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Core\Procurement\SupplierReceipt\{SupplierReceipt, SupplierReceiptLine};
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Procurement\{SupplierInvoiceWriterPort, SupplierPaymentWriterPort, SupplierReceiptWriterPort};
use App\Ports\Out\Inventory\InventoryMovementWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use App\Ports\Out\AuditLogPort;
use DateTimeImmutable;
use Throwable;

final class CreateSupplierInvoiceFlowHandler
{
    public function __construct(
        private SupplierInvoiceWriterPort $invoiceWriter,
        private SupplierReceiptWriterPort $receiptWriter,
        private SupplierPaymentWriterPort $paymentWriter,
        private InventoryMovementWriterPort $movementWriter,
        private TransactionManagerPort $transactions,
        private UuidPort $uuid,
        private SupplierService $supplierService,
        private SupplierInvoiceFactory $invoiceFactory,
        private InventoryProjectionService $projection,
        private AuditLogPort $audit
    ) {}

    public function handle(string $pt, string $tglKirim, array $lines, bool $autoRec = true, ?string $tglTerima = null): Result
    {
        $started = false;
        try {
            $dateKirim = DateTimeImmutable::createFromFormat('!Y-m-d', trim($tglKirim)) ?: throw new DomainException('Tgl kirim tidak valid.');
            $dateTerima = $autoRec ? (DateTimeImmutable::createFromFormat('!Y-m-d', trim($tglTerima ?? $tglKirim)) ?: throw new DomainException('Tgl terima tidak valid.')) : null;

            $this->transactions->begin(); $started = true;
            $supplier = $this->supplierService->resolve($pt);
            $invoice = SupplierInvoice::create($this->uuid->generate(), $supplier->id(), $dateKirim, $this->invoiceFactory->makeLines($lines));
            
            $this->invoiceWriter->create($invoice);
            $payment = SupplierPayment::create($this->uuid->generate(), $invoice->id(), $invoice->grandTotalRupiah(), $dateKirim, SupplierPayment::PROOF_STATUS_PENDING, null);
            $this->paymentWriter->create($payment);

            if ($autoRec && $dateTerima) $this->processAutoReceive($invoice, $dateTerima);

            // ADR-0008: Audit-First Mutation
            $this->audit->record('supplier_invoice_flow_completed', [
                'invoice_id' => $invoice->id(),
                'supplier_name' => $supplier->namaPtPengirim(),
                'grand_total' => $invoice->grandTotalRupiah()->amount(),
                'auto_received' => $autoRec
            ]);

            $this->transactions->commit();
            return Result::success(['id' => $invoice->id()], 'Flow Supplier Invoice Berhasil.');
        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return Result::failure($e->getMessage(), ['supplier_invoice' => ['INVALID_SUPPLIER_INVOICE']]);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }

    private function processAutoReceive(SupplierInvoice $inv, DateTimeImmutable $date): void
    {
        $rLines = array_map(fn($l) => SupplierReceiptLine::create($this->uuid->generate(), $l->id(), $l->qtyPcs()), $inv->lines());
        $receipt = SupplierReceipt::create($this->uuid->generate(), $inv->id(), $date, $rLines);
        $movements = array_map(fn($rl, $il) => InventoryMovement::create($this->uuid->generate(), $il->productId(), 'stock_in', 'supplier_receipt_line', $rl->id(), $date, $rl->qtyDiterima(), $il->unitCostRupiah()), $rLines, $inv->lines());

        $this->receiptWriter->create($receipt);
        $this->movementWriter->createMany($movements);
        $this->projection->applyMovements($movements);
    }
}
