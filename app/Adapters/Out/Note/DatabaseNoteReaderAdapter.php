<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Adapters\Out\Note\Mappers\{NoteMapper, WorkItemMapper};
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\{ExternalPurchaseLine, ServiceDetail, StoreStockLine};
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteReaderAdapter implements NoteReaderPort
{
    public function getById(string $id): ?Note
    {
        $noteRow = DB::table('notes')->where('id', trim($id))->first();
        if (!$noteRow) return null;

        $itemRows = DB::table('work_items')->where('note_id', $noteRow->id)->orderBy('line_no')->get()->all();
        $ids = array_map(fn($r) => (string) $r->id, $itemRows);

        $details = $this->loadDetails($ids);
        $externals = $this->loadExternals($ids);
        $stocks = $this->loadStocks($ids);

        $workItems = array_map(
            fn($r) => WorkItemMapper::map($r, $details, $externals, $stocks),
            $itemRows
        );

        return NoteMapper::map($noteRow, $workItems);
    }

    private function loadDetails(array $ids): array {
        $res = [];
        foreach (DB::table('work_item_service_details')->whereIn('work_item_id', $ids)->get() as $r) {
            $res[(string)$r->work_item_id] = ServiceDetail::rehydrate((string)$r->service_name, Money::fromInt((int)$r->service_price_rupiah), (string)$r->part_source);
        }
        return $res;
    }

    private function loadExternals(array $ids): array {
        $res = [];
        foreach (DB::table('work_item_external_purchase_lines')->whereIn('work_item_id', $ids)->orderBy('id')->get() as $r) {
            $res[(string)$r->work_item_id][] = ExternalPurchaseLine::rehydrate((string)$r->id, (string)$r->cost_description, Money::fromInt((int)$r->unit_cost_rupiah), (int)$r->qty);
        }
        return $res;
    }

    private function loadStocks(array $ids): array {
        $res = [];
        foreach (DB::table('work_item_store_stock_lines')->whereIn('work_item_id', $ids)->orderBy('id')->get() as $r) {
            $res[(string)$r->work_item_id][] = StoreStockLine::rehydrate((string)$r->id, (string)$r->product_id, (int)$r->qty, Money::fromInt((int)$r->line_total_rupiah));
        }
        return $res;
    }
}
