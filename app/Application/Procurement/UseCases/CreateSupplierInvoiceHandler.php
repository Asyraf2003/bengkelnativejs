<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\{SupplierService, SupplierInvoiceFactory};
use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use App\Ports\Out\AuditLogPort;
use DateTimeImmutable;
use Throwable;

final class CreateSupplierInvoiceHandler
{
    public function __construct(
        private SupplierInvoiceWriterPort $writer,
        private TransactionManagerPort $transactions,
        private UuidPort $uuid,
        private SupplierService $supplierService,
        private SupplierInvoiceFactory $invoiceFactory,
        private AuditLogPort $audit
    ) {}

    public function handle(string $pt, string $tgl, array $lines): Result
    {
        $started = false;
        try {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($tgl)) ?: throw new DomainException('Format tanggal salah.');
            $invoiceLines = $this->invoiceFactory->makeLines($lines);

            $this->transactions->begin(); $started = true;
            $supplier = $this->supplierService->resolve($pt);
            
            $invoice = SupplierInvoice::create($this->uuid->generate(), $supplier->id(), $date, $invoiceLines);
            $this->writer->create($invoice);

            $this->audit->record('supplier_invoice_created', [
                'invoice_id' => $invoice->id(),
                'supplier_id' => $supplier->id(),
                'total' => $invoice->grandTotalRupiah()->amount()
            ]);

            $this->transactions->commit();
            return Result::success(['id' => $invoice->id()], 'Supplier invoice berhasil dibuat.');

        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return Result::failure($e->getMessage(), ['supplier_invoice' => ['INVALID_SUPPLIER_INVOICE']]);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }
}
