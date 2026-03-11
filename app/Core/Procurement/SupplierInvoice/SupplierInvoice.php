<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

final class SupplierInvoice
{
    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private function __construct(
        private string $id,
        private string $supplierId,
        private DateTimeImmutable $tanggalPengiriman,
        private DateTimeImmutable $jatuhTempo,
        private array $lines,
        private Money $grandTotalRupiah,
    ) {
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    public static function create(
        string $id,
        string $supplierId,
        DateTimeImmutable $tanggalPengiriman,
        array $lines,
    ): self {
        self::assertValid($id, $supplierId, $lines);

        return new self(
            $id,
            trim($supplierId),
            $tanggalPengiriman,
            self::calculateJatuhTempo($tanggalPengiriman),
            array_values($lines),
            self::calculateGrandTotalRupiah($lines),
        );
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    public static function rehydrate(
        string $id,
        string $supplierId,
        DateTimeImmutable $tanggalPengiriman,
        array $lines,
    ): self {
        self::assertValid($id, $supplierId, $lines);

        return new self(
            $id,
            trim($supplierId),
            $tanggalPengiriman,
            self::calculateJatuhTempo($tanggalPengiriman),
            array_values($lines),
            self::calculateGrandTotalRupiah($lines),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function supplierId(): string
    {
        return $this->supplierId;
    }

    public function tanggalPengiriman(): DateTimeImmutable
    {
        return $this->tanggalPengiriman;
    }

    public function jatuhTempo(): DateTimeImmutable
    {
        return $this->jatuhTempo;
    }

    /**
     * @return list<SupplierInvoiceLine>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function grandTotalRupiah(): Money
    {
        return $this->grandTotalRupiah;
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private static function assertValid(
        string $id,
        string $supplierId,
        array $lines,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Supplier invoice id wajib ada.');
        }

        if (trim($supplierId) === '') {
            throw new DomainException('Supplier id pada supplier invoice wajib ada.');
        }

        if ($lines === []) {
            throw new DomainException('Supplier invoice minimal harus memiliki satu line.');
        }

        foreach ($lines as $line) {
            if ($line instanceof SupplierInvoiceLine === false) {
                throw new DomainException('Line supplier invoice tidak valid.');
            }
        }
    }

    private static function calculateJatuhTempo(
        DateTimeImmutable $tanggalPengiriman,
    ): DateTimeImmutable {
        $year = (int) $tanggalPengiriman->format('Y');
        $month = (int) $tanggalPengiriman->format('n');
        $day = (int) $tanggalPengiriman->format('j');

        $targetMonth = $month + 1;
        $targetYear = $year;

        if ($targetMonth > 12) {
            $targetMonth = 1;
            $targetYear++;
        }

        $firstDayOfTargetMonth = new DateTimeImmutable(
            sprintf('%04d-%02d-01', $targetYear, $targetMonth)
        );

        $lastDayOfTargetMonth = (int) $firstDayOfTargetMonth
            ->modify('last day of this month')
            ->format('j');

        $targetDay = min($day, $lastDayOfTargetMonth);

        return $tanggalPengiriman->setDate($targetYear, $targetMonth, $targetDay);
    }

    /**
     * @param list<SupplierInvoiceLine> $lines
     */
    private static function calculateGrandTotalRupiah(array $lines): Money
    {
        $total = Money::zero();

        foreach ($lines as $line) {
            $total = $total->add($line->lineTotalRupiah());
        }

        return $total;
    }
}
