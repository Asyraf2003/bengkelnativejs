<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class StoreTransactionWorkspaceGrandTotalCalculator
{
    public static function calculate(mixed $items): int
    {
        if (! is_array($items)) {
            return 0;
        }

        $total = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $total += StoreTransactionWorkspaceGrandTotalItemCalculator::calculate($item);
        }

        return $total;
    }
}
