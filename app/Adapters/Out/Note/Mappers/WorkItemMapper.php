<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Mappers;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\ValueObjects\Money;
use stdClass;

final class WorkItemMapper
{
    /**
     * @param array<string, mixed> $serviceDetails
     * @param array<string, list<mixed>> $externalLines
     * @param array<string, list<mixed>> $stockLines
     */
    public static function map(
        stdClass $row,
        array $serviceDetails,
        array $externalLines,
        array $stockLines
    ): WorkItem {
        $id = (string) $row->id;
        return WorkItem::rehydrate(
            $id,
            (string) $row->note_id,
            (int) $row->line_no,
            (string) $row->transaction_type,
            (string) $row->status,
            Money::fromInt((int) $row->subtotal_rupiah),
            $serviceDetails[$id] ?? null,
            $externalLines[$id] ?? [],
            $stockLines[$id] ?? []
        );
    }
}
