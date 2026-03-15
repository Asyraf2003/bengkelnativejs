<?php

declare(strict_types=1);

namespace App\Core\Note\WorkItem;

use App\Core\Shared\ValueObjects\Money;

final class WorkItem
{
    public const STATUS_OPEN = 'open', STATUS_DONE = 'done', STATUS_CANCELED = 'canceled';
    public const TYPE_SERVICE_ONLY = 'service_only';
    public const TYPE_SERVICE_WITH_EXTERNAL_PURCHASE = 'service_with_external_purchase';
    public const TYPE_STORE_STOCK_SALE_ONLY = 'store_stock_sale_only';
    public const TYPE_SERVICE_WITH_STORE_STOCK_PART = 'service_with_store_stock_part';

    private function __construct(
        private string $id,
        private string $noteId,
        private int $lineNo,
        private string $transactionType,
        private string $status,
        private Money $subtotalRupiah,
        private ?ServiceDetail $serviceDetail,
        private array $externalPurchaseLines,
        private array $storeStockLines,
    ) {}

    public static function createServiceOnly(string $id, string $noteId, int $lineNo, ServiceDetail $sd, string $st = self::STATUS_OPEN): self {
        WorkItemGuard::assertValidCommon($id, $noteId, $lineNo, $st);
        WorkItemGuard::validateState(self::TYPE_SERVICE_ONLY, $sd, [], []);
        return new self(trim($id), trim($noteId), $lineNo, self::TYPE_SERVICE_ONLY, trim($st), $sd->servicePriceRupiah(), $sd, [], []);
    }

    public static function createServiceWithExternalPurchase(string $id, string $noteId, int $lineNo, ServiceDetail $sd, array $ext, string $st = self::STATUS_OPEN): self {
        WorkItemGuard::assertValidCommon($id, $noteId, $lineNo, $st);
        WorkItemGuard::validateState(self::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, $sd, $ext, []);
        $sub = WorkItemGuard::calculateSubtotal(self::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, $sd, $ext, []);
        return new self(trim($id), trim($noteId), $lineNo, self::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, trim($st), $sub, $sd, array_values($ext), []);
    }

    public static function createStoreStockSaleOnly(string $id, string $noteId, int $lineNo, array $sto, string $st = self::STATUS_OPEN): self {
        WorkItemGuard::assertValidCommon($id, $noteId, $lineNo, $st);
        WorkItemGuard::validateState(self::TYPE_STORE_STOCK_SALE_ONLY, null, [], $sto);
        $sub = WorkItemGuard::calculateSubtotal(self::TYPE_STORE_STOCK_SALE_ONLY, null, [], $sto);
        return new self(trim($id), trim($noteId), $lineNo, self::TYPE_STORE_STOCK_SALE_ONLY, trim($st), $sub, null, [], array_values($sto));
    }

    public static function createServiceWithStoreStockPart(string $id, string $noteId, int $lineNo, ServiceDetail $sd, array $sto, string $st = self::STATUS_OPEN): self {
        WorkItemGuard::assertValidCommon($id, $noteId, $lineNo, $st);
        WorkItemGuard::validateState(self::TYPE_SERVICE_WITH_STORE_STOCK_PART, $sd, [], $sto);
        $sub = WorkItemGuard::calculateSubtotal(self::TYPE_SERVICE_WITH_STORE_STOCK_PART, $sd, [], $sto);
        return new self(trim($id), trim($noteId), $lineNo, self::TYPE_SERVICE_WITH_STORE_STOCK_PART, trim($st), $sub, $sd, [], array_values($sto));
    }

    public static function rehydrate(string $id, string $nId, int $lNo, string $type, string $st, Money $sub, ?ServiceDetail $sd, array $ext = [], array $sto = []): self {
        WorkItemGuard::assertValidCommon($id, $nId, $lNo, $st);
        WorkItemGuard::assertValidTransactionType($type);
        WorkItemGuard::validateState($type, $sd, $ext, $sto);
        return new self(trim($id), trim($nId), $lNo, trim($type), trim($st), $sub, $sd, array_values($ext), array_values($sto));
    }

    public function markDone(): void { $this->status = self::STATUS_DONE; }
    public function cancel(): void { $this->status = self::STATUS_CANCELED; }
    public function id(): string { return $this->id; }
    public function noteId(): string { return $this->noteId; }
    public function lineNo(): int { return $this->lineNo; }
    public function transactionType(): string { return $this->transactionType; }
    public function status(): string { return $this->status; }
    public function subtotalRupiah(): Money { return $this->subtotalRupiah; }
    public function serviceDetail(): ?ServiceDetail { return $this->serviceDetail; }
    public function externalPurchaseLines(): array { return $this->externalPurchaseLines; }
    public function storeStockLines(): array { return $this->storeStockLines; }
}
