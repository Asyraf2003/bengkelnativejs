<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

final class CreateNoteRevisionSurplusRefundDueResult
{
    /** @param array<string, mixed> $data */
    private function __construct(
        private readonly bool $success,
        private readonly ?string $message,
        private readonly array $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function success(array $data, ?string $message = null): self
    {
        return new self(true, $message, $data);
    }

    /** @param array<string, mixed> $data */
    public static function failure(?string $message = null, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }
}
