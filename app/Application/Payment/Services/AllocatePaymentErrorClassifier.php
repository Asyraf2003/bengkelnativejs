<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;

final class AllocatePaymentErrorClassifier
{
    public function classify(DomainException $e): Result
    {
        $msg = $e->getMessage();
        $code = 'INVALID_PAYMENT_ALLOCATION';

        if (str_contains($msg, 'tidak ditemukan')) {
            $code = 'PAYMENT_INVALID_TARGET';
        } elseif (str_contains($msg, 'melebihi sisa payment')) {
            $code = 'PAYMENT_OVER_ALLOCATION';
        } elseif (str_contains($msg, 'melebihi outstanding note')) {
            $code = 'PAYMENT_EXCEEDS_OUTSTANDING';
        }

        return Result::failure($msg, ['payment' => [$code]]);
    }
}
