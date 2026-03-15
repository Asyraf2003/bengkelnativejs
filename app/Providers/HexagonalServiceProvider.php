<?php

// @audit-skip: line-limit

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Audit\DatabaseAuditLogAdapter;
use App\Adapters\Out\Auth\LaravelUuidAdapter;
use App\Adapters\Out\Clock\SystemClockAdapter;
use App\Adapters\Out\IdentityAccess\DatabaseActorAccessReaderAdapter;
use App\Adapters\Out\IdentityAccess\DatabaseAdminTransactionCapabilityStateAdapter;
use App\Adapters\Out\Inventory\DatabaseInventoryMovementReaderAdapter;
use App\Adapters\Out\Inventory\DatabaseInventoryMovementWriterAdapter;
use App\Adapters\Out\Inventory\DatabaseProductInventoryCostingProjectionWriterAdapter;
use App\Adapters\Out\Inventory\DatabaseProductInventoryCostingReaderAdapter;
use App\Adapters\Out\Inventory\DatabaseProductInventoryCostingWriterAdapter;
use App\Adapters\Out\Inventory\DatabaseProductInventoryProjectionWriterAdapter;
use App\Adapters\Out\Inventory\DatabaseProductInventoryReaderAdapter;
use App\Adapters\Out\Inventory\DatabaseProductInventoryWriterAdapter;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Adapters\Out\Note\DatabaseNoteWriterAdapter;
use App\Adapters\Out\Note\DatabaseWorkItemWriterAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentWriterAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationWriterAdapter;
use App\Adapters\Out\Persistence\DatabaseTransactionManagerAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceLineReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptLineReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierWriterAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseProductDuplicateCheckerAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseProductReaderAdapter;
use App\Adapters\Out\ProductCatalog\DatabaseProductWriterAdapter;
use App\Application\Inventory\Policies\DefaultNegativeStockPolicy;
use App\Application\Inventory\Services\InventoryProjectionService;
use App\Application\Inventory\Services\IssueInventoryOperation;
use App\Application\Note\Services\AddWorkItemErrorClassifier;
use App\Application\Note\Services\WorkItemFactory;
use App\Application\Note\Services\WorkItemStatusTransitionService;
use App\Application\Procurement\Services\SupplierInvoiceFactory;
use App\Application\Procurement\Services\SupplierReceiptFactory;
use App\Application\Procurement\Services\SupplierService;
use App\Application\System\Health\HealthCheckHandler;
use App\Core\Inventory\Policies\NegativeStockPolicy;
use App\Core\Note\Policies\NoteAddabilityPolicy;
use App\Core\ProductCatalog\Policies\MinSellingPricePolicy;
use App\Ports\In\HealthCheckUseCase;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\IdentityAccess\AdminTransactionCapabilityStatePort;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Inventory\InventoryMovementWriterPort;
use App\Ports\Out\Inventory\ProductInventoryCostingProjectionWriterPort;
use App\Ports\Out\Inventory\ProductInventoryCostingReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingWriterPort;
use App\Ports\Out\Inventory\ProductInventoryProjectionWriterPort;
use App\Ports\Out\Inventory\ProductInventoryReaderPort;
use App\Ports\Out\Inventory\ProductInventoryWriterPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\Payment\CustomerPaymentReaderPort;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentAllocationWriterPort;
use App\Ports\Out\Procurement\SupplierInvoiceLineReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;
use App\Ports\Out\Procurement\SupplierReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptLineReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptWriterPort;
use App\Ports\Out\Procurement\SupplierWriterPort;
use App\Ports\Out\ProductCatalog\ProductDuplicateCheckerPort;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\ProductCatalog\ProductWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Illuminate\Support\ServiceProvider;

class HexagonalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- Inbound Ports / Use Cases ---
        $this->app->bind(HealthCheckUseCase::class, HealthCheckHandler::class);

        // --- Infrastructure Ports ---
        $this->app->singleton(ClockPort::class, SystemClockAdapter::class);
        $this->app->singleton(UuidPort::class, LaravelUuidAdapter::class);
        $this->app->singleton(AuditLogPort::class, DatabaseAuditLogAdapter::class);
        $this->app->singleton(TransactionManagerPort::class, DatabaseTransactionManagerAdapter::class);

        // --- Domain Policies (Blueprint 1.6) ---
        $this->app->singleton(NegativeStockPolicy::class, DefaultNegativeStockPolicy::class);
        $this->app->singleton(MinSellingPricePolicy::class);
        $this->app->singleton(NoteAddabilityPolicy::class);

        // --- Application Services & Factories (Decoupled Logic) ---
        $this->app->singleton(InventoryProjectionService::class);
        $this->app->singleton(IssueInventoryOperation::class);
        $this->app->singleton(WorkItemFactory::class);
        $this->app->singleton(WorkItemStatusTransitionService::class);
        $this->app->singleton(AddWorkItemErrorClassifier::class);
        $this->app->singleton(SupplierService::class);
        $this->app->singleton(SupplierInvoiceFactory::class);
        $this->app->singleton(SupplierReceiptFactory::class);

        // --- Identity & Access ---
        $this->app->singleton(ActorAccessReaderPort::class, DatabaseActorAccessReaderAdapter::class);
        $this->app->singleton(AdminTransactionCapabilityStatePort::class, DatabaseAdminTransactionCapabilityStateAdapter::class);

        // --- Product Catalog ---
        $this->app->singleton(ProductReaderPort::class, DatabaseProductReaderAdapter::class);
        $this->app->singleton(ProductWriterPort::class, DatabaseProductWriterAdapter::class);
        $this->app->singleton(ProductDuplicateCheckerPort::class, DatabaseProductDuplicateCheckerAdapter::class);

        // --- Procurement ---
        $this->app->singleton(SupplierReaderPort::class, DatabaseSupplierReaderAdapter::class);
        $this->app->singleton(SupplierWriterPort::class, DatabaseSupplierWriterAdapter::class);
        $this->app->singleton(SupplierInvoiceWriterPort::class, DatabaseSupplierInvoiceWriterAdapter::class);
        $this->app->singleton(SupplierInvoiceReaderPort::class, DatabaseSupplierInvoiceReaderAdapter::class);
        $this->app->singleton(SupplierInvoiceLineReaderPort::class, DatabaseSupplierInvoiceLineReaderAdapter::class);
        $this->app->singleton(SupplierReceiptLineReaderPort::class, DatabaseSupplierReceiptLineReaderAdapter::class);
        $this->app->singleton(SupplierReceiptWriterPort::class, DatabaseSupplierReceiptWriterAdapter::class);
        $this->app->singleton(SupplierPaymentWriterPort::class, DatabaseSupplierPaymentWriterAdapter::class);
        $this->app->singleton(SupplierPaymentReaderPort::class, DatabaseSupplierPaymentReaderAdapter::class);

        // --- Inventory ---
        $this->app->singleton(InventoryMovementReaderPort::class, DatabaseInventoryMovementReaderAdapter::class);
        $this->app->singleton(InventoryMovementWriterPort::class, DatabaseInventoryMovementWriterAdapter::class);
        $this->app->singleton(ProductInventoryReaderPort::class, DatabaseProductInventoryReaderAdapter::class);
        $this->app->singleton(ProductInventoryWriterPort::class, DatabaseProductInventoryWriterAdapter::class);
        $this->app->singleton(ProductInventoryProjectionWriterPort::class, DatabaseProductInventoryProjectionWriterAdapter::class);
        $this->app->singleton(ProductInventoryCostingReaderPort::class, DatabaseProductInventoryCostingReaderAdapter::class);
        $this->app->singleton(ProductInventoryCostingWriterPort::class, DatabaseProductInventoryCostingWriterAdapter::class);
        $this->app->singleton(ProductInventoryCostingProjectionWriterPort::class, DatabaseProductInventoryCostingProjectionWriterAdapter::class);

        // --- Note ---
        $this->app->singleton(NoteReaderPort::class, DatabaseNoteReaderAdapter::class);
        $this->app->singleton(NoteWriterPort::class, DatabaseNoteWriterAdapter::class);
        $this->app->singleton(WorkItemWriterPort::class, DatabaseWorkItemWriterAdapter::class);

        // --- Payment ---
        $this->app->singleton(CustomerPaymentWriterPort::class, DatabaseCustomerPaymentWriterAdapter::class);
        $this->app->singleton(CustomerPaymentReaderPort::class, DatabaseCustomerPaymentReaderAdapter::class);
        $this->app->singleton(PaymentAllocationWriterPort::class, DatabasePaymentAllocationWriterAdapter::class);
        $this->app->singleton(PaymentAllocationReaderPort::class, DatabasePaymentAllocationReaderAdapter::class);
    }
}
