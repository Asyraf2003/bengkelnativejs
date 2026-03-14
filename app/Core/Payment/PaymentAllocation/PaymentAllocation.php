<?php

declare(strict_types=1);

namespace App\Core\Payment\PaymentAllocation;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class PaymentAllocation
{
    private function __construct(
        private string $id,
        private string $customerPaymentId,
        private string $noteId,
        private Money $amountRupiah,
    ) {
    }

    public static function create(
        string $id,
        string $customerPaymentId,
        string $noteId,
        Money $amountRupiah,
    ): self {
        self::assertValid($id, $customerPaymentId, $noteId, $amountRupiah);

        return new self(
            trim($id),
            trim($customerPaymentId),
            trim($noteId),
            $amountRupiah,
        );
    }

    public static function rehydrate(
        string $id,
        string $customerPaymentId,
        string $noteId,
        Money $amountRupiah,
    ): self {
        self::assertValid($id, $customerPaymentId, $noteId, $amountRupiah);

        return new self(
            trim($id),
            trim($customerPaymentId),
            trim($noteId),
            $amountRupiah,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function customerPaymentId(): string
    {
        return $this->customerPaymentId;
    }

    public function noteId(): string
    {
        return $this->noteId;
    }

    public function amountRupiah(): Money
    {
        return $this->amountRupiah;
    }

    private static function assertValid(
        string $id,
        string $customerPaymentId,
        string $noteId,
        Money $amountRupiah,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Payment allocation id wajib ada.');
        }

        if (trim($customerPaymentId) === '') {
            throw new DomainException('Customer payment id pada payment allocation wajib ada.');
        }

        if (trim($noteId) === '') {
            throw new DomainException('Note id pada payment allocation wajib ada.');
        }

        if ($amountRupiah->greaterThan(Money::zero()) === false) {
            throw new DomainException('Amount rupiah pada payment allocation harus lebih besar dari nol.');
        }
    }
}
