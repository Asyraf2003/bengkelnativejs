<?php

declare(strict_types=1);

namespace App\Adapters\Out\Inventory;

use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseInventoryMovementReaderAdapter implements InventoryMovementReaderPort
{
    /**
     * @return list<InventoryMovement>
     */
    public function getAll(): array
    {
        $rows = DB::table('inventory_movements')
            ->select([
                'id',
                'product_id',
                'movement_type',
                'source_type',
                'source_id',
                'tanggal_mutasi',
                'qty_delta',
                'unit_cost_rupiah',
            ])
            ->orderBy('tanggal_mutasi')
            ->orderBy('id')
            ->get();

        $movements = [];

        foreach ($rows as $row) {
            $tanggalMutasi = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $row->tanggal_mutasi);

            if ($tanggalMutasi === false) {
                throw new RuntimeException('Data tanggal mutasi inventory tidak valid.');
            }

            $movements[] = InventoryMovement::rehydrate(
                (string) $row->id,
                (string) $row->product_id,
                (string) $row->movement_type,
                (string) $row->source_type,
                (string) $row->source_id,
                $tanggalMutasi,
                (int) $row->qty_delta,
                Money::fromInt((int) $row->unit_cost_rupiah),
            );
        }

        return $movements;
    }
}
