<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use Throwable;

final class PaymentConcurrencyTransientExceptionClassifier
{
    public function isTransient(Throwable $e): bool
    {
        $sqlState = $this->sqlState($e);
        $driverCode = $this->driverCode($e);
        $message = mb_strtolower($e->getMessage());

        if ($sqlState === '40001') {
            return true;
        }

        if (in_array($driverCode, ['1020', '1205', '1213'], true)) {
            return true;
        }

        return $sqlState === 'HY000'
            && (
                str_contains($message, 'record has changed since last read')
                || str_contains($message, 'deadlock')
                || str_contains($message, 'lock wait timeout exceeded')
            );
    }

    private function sqlState(Throwable $e): string
    {
        $errorInfo = $this->errorInfo($e);

        return (string) ($errorInfo[0] ?? $e->getCode());
    }

    private function driverCode(Throwable $e): string
    {
        $errorInfo = $this->errorInfo($e);

        return (string) ($errorInfo[1] ?? '');
    }

    /**
     * @return array<int, mixed>
     */
    private function errorInfo(Throwable $e): array
    {
        if (!property_exists($e, 'errorInfo')) {
            return [];
        }

        $value = $e->errorInfo;

        return is_array($value) ? $value : [];
    }
}
