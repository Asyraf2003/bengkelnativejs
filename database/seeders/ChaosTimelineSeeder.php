<?php

namespace Database\Seeders;

use App\Application\UseCases\Invoices\CreateSupplierInvoiceUseCase;
use App\Application\UseCases\Invoices\MarkPaidSupplierInvoiceUseCase;
use App\Application\UseCases\Transactions\CancelDraftCustomerTransactionUseCase;
use App\Application\UseCases\Transactions\CreateDraftCustomerTransactionUseCase;
use App\Application\UseCases\Transactions\MarkPaidCustomerTransactionUseCase;
use App\Application\UseCases\Transactions\RefundCustomerTransactionUseCase;
use App\Application\UseCases\Transactions\UpdateDraftCustomerTransactionUseCase;
use App\Models\CustomerTransaction;
use App\Models\Product;
use App\Models\ProductInventory;
use Carbon\CarbonImmutable;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Throwable;

class ChaosTimelineSeeder extends Seeder
{
    public function run(): void
    {
        $seed = (int) env('CHAOS_SEED', 20260307) + 701;

        $invoiceMin = (int) env('CHAOS_INVOICES_MIN', 30);
        $invoiceMax = (int) env('CHAOS_INVOICES_MAX', 50);
        $maxItemsPerInvoice = (int) env('CHAOS_INVOICE_MAX_ITEMS', 6);
        $invoicePaidRatioPercent = (int) env('CHAOS_INVOICE_PAID_RATIO', 70);

        $rangeDays = (int) env('CHAOS_RANGE_DAYS', 150);

        $txDailyMin = (int) env('CHAOS_TX_DAILY_MIN', 25);
        $txDailyMax = (int) env('CHAOS_TX_DAILY_MAX', 60);
        $txCustomerPool = (int) env('CHAOS_TX_CUSTOMER_POOL', 28);
        $txDayCustomersMin = (int) env('CHAOS_TX_DAY_CUSTOMERS_MIN', 8);
        $txDayCustomersMax = (int) env('CHAOS_TX_DAY_CUSTOMERS_MAX', 14);
        $txOpenRatio = (int) env('CHAOS_TX_OPEN_RATIO', 10);
        $txCancelRatio = (int) env('CHAOS_TX_CANCEL_RATIO', 14);
        $txUpdateRatio = (int) env('CHAOS_TX_UPDATE_RATIO', 28);
        $txRefundRatio = (int) env('CHAOS_TX_REFUND_RATIO', 18);
        $txResolveLagMax = (int) env('CHAOS_TX_RESOLVE_LAG_MAX', 10);
        $txUpdateLagMax = (int) env('CHAOS_TX_UPDATE_LAG_MAX', 3);
        $txRefundLagMax = (int) env('CHAOS_TX_REFUND_LAG_MAX', 10);

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'code', 'sale_price'])
            ->values();

        if ($products->count() < 10) {
            throw new \RuntimeException('Product aktif terlalu sedikit untuk chaos timeline seeder.');
        }

        $today = CarbonImmutable::today();
        $start = $today->subDays($rangeDays);

        $customerPool = $this->buildCustomerPool($faker, $txCustomerPool);

        $events = [];
        $seq = 1;

        // ------------------------------
        // Invoice specs -> timeline events
        // ------------------------------
        $supplierNames = [
            'PT Sumber Jaya Motor',
            'CV Prima Onderdil',
            'UD Makmur Sparepart',
            'PT Niaga Roda Abadi',
            'CV Sukses Mandiri Teknik',
            'UD Sentosa Motorindo',
        ];

        $invoiceCount = $faker->numberBetween($invoiceMin, $invoiceMax);

        for ($i = 1; $i <= $invoiceCount; $i++) {
            $deliveredAt = CarbonImmutable::instance(
                $faker->dateTimeBetween($start, $today)
            )->startOfDay();

            $dueAt = $deliveredAt->addMonthNoOverflow();

            $pickedProducts = $this->pickRandomSubset(
                $products->all(),
                $faker->numberBetween(1, max(1, $maxItemsPerInvoice)),
                $faker
            );

            $items = [];
            foreach ($pickedProducts as $product) {
                $qty = $faker->numberBetween(5, 40);

                $targetUnitCost = max(
                    1000,
                    (int) (round(((int) $product->sale_price * $faker->randomFloat(2, 0.45, 0.90)) / 1000) * 1000)
                );

                $items[] = [
                    'product_id' => (int) $product->id,
                    'qty' => (int) $qty,
                    'total_cost' => (int) ($qty * $targetUnitCost),
                ];
            }

            $invoiceClientKey = sprintf('INV-%s-%04d', $deliveredAt->format('Ymd'), $i);

            $events[] = [
                'type' => 'invoice_create',
                'event_date' => $deliveredAt->toDateString(),
                'priority' => 10,
                'seq' => $seq++,
                'client_key' => $invoiceClientKey,
                'payload' => [
                    'invoice_no' => sprintf('CHINV-%s-%04d', $deliveredAt->format('Ym'), $i),
                    'supplier_name' => $faker->randomElement($supplierNames),
                    'delivered_at' => $deliveredAt->toDateString(),
                    'due_at' => $dueAt->toDateString(),
                    'note' => 'chaos timeline invoice',
                    'items' => $items,
                ],
            ];

            $shouldBePaid = $faker->numberBetween(1, 100) <= $invoicePaidRatioPercent;
            if ($shouldBePaid) {
                $maxPaidAt = $dueAt->lessThan($today) ? $dueAt : $today;

                if ($maxPaidAt->greaterThanOrEqualTo($deliveredAt)) {
                    $paidAt = CarbonImmutable::instance(
                        $faker->dateTimeBetween($deliveredAt, $maxPaidAt)
                    )->startOfDay();

                    $events[] = [
                        'type' => 'invoice_mark_paid',
                        'event_date' => $paidAt->toDateString(),
                        'priority' => 90,
                        'seq' => $seq++,
                        'client_key' => $invoiceClientKey,
                        'payload' => [
                            'paid_at' => $paidAt->toDateString(),
                        ],
                    ];
                }
            }
        }

        // ------------------------------
        // Transaction specs -> timeline events
        // ------------------------------
        $days = $rangeDays + 1;

        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $day = $start->addDays($dayOffset)->startOfDay();

            $txCount = $faker->numberBetween($txDailyMin, $txDailyMax);
            $dayCustomerCount = min(
                count($customerPool),
                $faker->numberBetween($txDayCustomersMin, $txDayCustomersMax)
            );

            $dayCustomers = $this->pickRandomSubset($customerPool, $dayCustomerCount, $faker);

            for ($i = 1; $i <= $txCount; $i++) {
                $txClientKey = sprintf('TX-%s-%04d', $day->format('Ymd'), $i);
                $customerName = (string) $faker->randomElement($dayCustomers);

                $createBlueprint = $this->makeLineBlueprint($faker);

                $events[] = [
                    'type' => 'tx_create',
                    'event_date' => $day->toDateString(),
                    'priority' => 20,
                    'seq' => $seq++,
                    'client_key' => $txClientKey,
                    'payload' => [
                        'customer_name' => $customerName,
                        'transacted_at' => $day->toDateString(),
                        'note' => sprintf('chaos-draft-%s', $txClientKey),
                        'blueprint' => $createBlueprint,
                    ],
                ];

                $willUpdate = $faker->numberBetween(1, 100) <= $txUpdateRatio;

                $statusRoll = $faker->numberBetween(1, 100);

                $finalAction = 'paid';
                if ($statusRoll <= $txOpenRatio) {
                    $finalAction = 'open';
                } elseif ($statusRoll <= ($txOpenRatio + $txCancelRatio)) {
                    $finalAction = 'cancel';
                }

                $resolveMaxDate = $day->addDays($txResolveLagMax);
                if ($resolveMaxDate->greaterThan($today)) {
                    $resolveMaxDate = $today;
                }

                $resolveDate = null;
                if ($finalAction !== 'open' && $resolveMaxDate->greaterThanOrEqualTo($day)) {
                    $resolveDate = CarbonImmutable::instance(
                        $faker->dateTimeBetween($day, $resolveMaxDate)
                    )->startOfDay();
                }

                if ($willUpdate) {
                    $updateMaxDate = $day->addDays($txUpdateLagMax);
                    if ($updateMaxDate->greaterThan($today)) {
                        $updateMaxDate = $today;
                    }
                    if ($resolveDate && $updateMaxDate->greaterThan($resolveDate)) {
                        $updateMaxDate = $resolveDate;
                    }

                    if ($updateMaxDate->greaterThanOrEqualTo($day)) {
                        $updateDate = CarbonImmutable::instance(
                            $faker->dateTimeBetween($day, $updateMaxDate)
                        )->startOfDay();

                        $events[] = [
                            'type' => 'tx_update',
                            'event_date' => $updateDate->toDateString(),
                            'priority' => 30,
                            'seq' => $seq++,
                            'client_key' => $txClientKey,
                            'payload' => [
                                'note' => sprintf('chaos-update-%s', $txClientKey),
                                'blueprint' => $this->makeLineBlueprint($faker),
                            ],
                        ];
                    }
                }

                if ($finalAction === 'cancel' && $resolveDate) {
                    $events[] = [
                        'type' => 'tx_cancel',
                        'event_date' => $resolveDate->toDateString(),
                        'priority' => 50,
                        'seq' => $seq++,
                        'client_key' => $txClientKey,
                        'payload' => [],
                    ];
                }

                if ($finalAction === 'paid' && $resolveDate) {
                    $events[] = [
                        'type' => 'tx_mark_paid',
                        'event_date' => $resolveDate->toDateString(),
                        'priority' => 40,
                        'seq' => $seq++,
                        'client_key' => $txClientKey,
                        'payload' => [
                            'paid_at' => $resolveDate->toDateString(),
                        ],
                    ];

                    $shouldRefund = $faker->numberBetween(1, 100) <= $txRefundRatio;
                    if ($shouldRefund) {
                        $refundMaxDate = $resolveDate->addDays($txRefundLagMax);
                        if ($refundMaxDate->greaterThan($today)) {
                            $refundMaxDate = $today;
                        }

                        if ($refundMaxDate->greaterThanOrEqualTo($resolveDate)) {
                            $refundDate = CarbonImmutable::instance(
                                $faker->dateTimeBetween($resolveDate, $refundMaxDate)
                            )->startOfDay();

                            $events[] = [
                                'type' => 'tx_refund',
                                'event_date' => $refundDate->toDateString(),
                                'priority' => 60,
                                'seq' => $seq++,
                                'client_key' => $txClientKey,
                                'payload' => [
                                    'refunded_at' => $refundDate->toDateString(),
                                ],
                            ];
                        }
                    }
                }
            }
        }

        usort($events, function (array $a, array $b): int {
            return [$a['event_date'], $a['priority'], $a['seq']]
                <=> [$b['event_date'], $b['priority'], $b['seq']];
        });

        /** @var CreateSupplierInvoiceUseCase $createInvoiceUseCase */
        $createInvoiceUseCase = app(CreateSupplierInvoiceUseCase::class);

        /** @var MarkPaidSupplierInvoiceUseCase $markPaidInvoiceUseCase */
        $markPaidInvoiceUseCase = app(MarkPaidSupplierInvoiceUseCase::class);

        /** @var CreateDraftCustomerTransactionUseCase $createDraftUseCase */
        $createDraftUseCase = app(CreateDraftCustomerTransactionUseCase::class);

        /** @var UpdateDraftCustomerTransactionUseCase $updateDraftUseCase */
        $updateDraftUseCase = app(UpdateDraftCustomerTransactionUseCase::class);

        /** @var MarkPaidCustomerTransactionUseCase $markPaidTxUseCase */
        $markPaidTxUseCase = app(MarkPaidCustomerTransactionUseCase::class);

        /** @var CancelDraftCustomerTransactionUseCase $cancelDraftUseCase */
        $cancelDraftUseCase = app(CancelDraftCustomerTransactionUseCase::class);

        /** @var RefundCustomerTransactionUseCase $refundTxUseCase */
        $refundTxUseCase = app(RefundCustomerTransactionUseCase::class);

        $invoiceMap = [];
        $transactionMap = [];

        $stats = [
            'invoice_created' => 0,
            'invoice_marked_paid' => 0,
            'tx_created' => 0,
            'tx_updated' => 0,
            'tx_paid' => 0,
            'tx_canceled' => 0,
            'tx_refunded' => 0,
            'skip_total' => 0,
            'skip_reasons' => [],
            'skip_samples' => [],
        ];

        foreach ($events as $event) {
            $type = (string) $event['type'];
            $clientKey = (string) $event['client_key'];
            $payload = (array) $event['payload'];

            try {
                if ($type === 'invoice_create') {
                    $invoiceId = $createInvoiceUseCase->execute([
                        'invoice_no' => (string) $payload['invoice_no'],
                        'supplier_name' => (string) $payload['supplier_name'],
                        'delivered_at' => (string) $payload['delivered_at'],
                        'due_at' => (string) $payload['due_at'],
                        'note' => (string) $payload['note'],
                        'items' => $payload['items'],
                    ]);

                    $invoiceMap[$clientKey] = (int) $invoiceId;
                    $stats['invoice_created']++;
                    continue;
                }

                if ($type === 'invoice_mark_paid') {
                    $invoiceId = $invoiceMap[$clientKey] ?? null;
                    if (!$invoiceId) {
                        $this->addSkip($stats, 'invoice_missing_map', $type, $clientKey, 'Invoice map tidak ditemukan.');
                        continue;
                    }

                    $markPaidInvoiceUseCase->execute([
                        'invoice_id' => (int) $invoiceId,
                        'paid_at' => (string) $payload['paid_at'],
                    ]);

                    $stats['invoice_marked_paid']++;
                    continue;
                }

                if ($type === 'tx_create') {
                    $lines = $this->materializeLines(
                        $payload['blueprint'],
                        $faker,
                        null
                    );

                    $txId = $createDraftUseCase->execute([
                        'customer_name' => (string) $payload['customer_name'],
                        'transacted_at' => (string) $payload['transacted_at'],
                        'note' => (string) $payload['note'],
                        'lines' => $lines,
                    ]);

                    $transactionMap[$clientKey] = (int) $txId;
                    $stats['tx_created']++;
                    continue;
                }

                $txId = $transactionMap[$clientKey] ?? null;
                if (!$txId) {
                    $this->addSkip($stats, 'tx_missing_map', $type, $clientKey, 'Transaction map tidak ditemukan.');
                    continue;
                }

                /** @var CustomerTransaction|null $transaction */
                $transaction = CustomerTransaction::query()
                    ->with('lines')
                    ->find($txId);

                if (! $transaction) {
                    $this->addSkip($stats, 'tx_missing_row', $type, $clientKey, 'Transaction row tidak ditemukan.');
                    continue;
                }

                if ($type === 'tx_update') {
                    if ($transaction->status !== 'draft') {
                        $this->addSkip(
                            $stats,
                            'tx_update_non_draft',
                            $type,
                            $clientKey,
                            "Status transaksi bukan draft: {$transaction->status}"
                        );
                        continue;
                    }

                    $lines = $this->materializeLines(
                        $payload['blueprint'],
                        $faker,
                        $transaction
                    );

                    $updateDraftUseCase->execute([
                        'transaction_id' => (int) $transaction->id,
                        'customer_name' => (string) $transaction->customer_name,
                        'transacted_at' => (string) $transaction->transacted_at->toDateString(),
                        'note' => (string) $payload['note'],
                        'lines' => $lines,
                    ]);

                    $stats['tx_updated']++;
                    continue;
                }

                if ($type === 'tx_mark_paid') {
                    if ($transaction->status !== 'draft') {
                        $this->addSkip(
                            $stats,
                            'tx_mark_paid_non_draft',
                            $type,
                            $clientKey,
                            "Status transaksi bukan draft: {$transaction->status}"
                        );
                        continue;
                    }

                    $markPaidTxUseCase->execute([
                        'transaction_id' => (int) $transaction->id,
                        'paid_at' => (string) $payload['paid_at'],
                    ]);

                    $stats['tx_paid']++;
                    continue;
                }

                if ($type === 'tx_cancel') {
                    if ($transaction->status !== 'draft') {
                        $this->addSkip(
                            $stats,
                            'tx_cancel_non_draft',
                            $type,
                            $clientKey,
                            "Status transaksi bukan draft: {$transaction->status}"
                        );
                        continue;
                    }

                    $cancelDraftUseCase->execute((int) $transaction->id);
                    $stats['tx_canceled']++;
                    continue;
                }

                if ($type === 'tx_refund') {
                    $refunded = $this->executeSyntheticRefund(
                        (int) $transaction->id,
                        (string) $payload['refunded_at'],
                        $faker,
                        $refundTxUseCase
                    );

                    if ($refunded) {
                        $stats['tx_refunded']++;
                    } else {
                        $this->addSkip(
                            $stats,
                            'tx_refund_not_eligible',
                            $type,
                            $clientKey,
                            'Refund synthetic tidak eligible / tidak ada line yang bisa direfund.'
                        );
                    }

                    continue;
                }

                $this->addSkip($stats, 'unknown_event_type', $type, $clientKey, 'Event type tidak dikenali.');
            } catch (Throwable $e) {
                $reason = $this->classifyExceptionReason($type, $e);
                $this->addSkip($stats, $reason, $type, $clientKey, $e->getMessage());
            }
        }

        $this->command?->info("Chaos timeline invoices created: {$stats['invoice_created']}");
        $this->command?->info("Chaos timeline invoices marked paid: {$stats['invoice_marked_paid']}");
        $this->command?->info("Chaos timeline transactions created: {$stats['tx_created']}");
        $this->command?->info("Chaos timeline transactions updated: {$stats['tx_updated']}");
        $this->command?->info("Chaos timeline transactions paid: {$stats['tx_paid']}");
        $this->command?->info("Chaos timeline transactions canceled: {$stats['tx_canceled']}");
        $this->command?->info("Chaos timeline transactions refunded: {$stats['tx_refunded']}");
        $this->command?->warn("Chaos timeline events skipped total: {$stats['skip_total']}");

        foreach ($stats['skip_reasons'] as $reason => $count) {
            $this->command?->warn(" - {$reason}: {$count}");
        }

        foreach ($stats['skip_samples'] as $reason => $samples) {
            $this->command?->line("   samples[{$reason}]");
            foreach ($samples as $sample) {
                $this->command?->line("     {$sample}");
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function buildCustomerPool(Generator $faker, int $size): array
    {
        $customers = [];

        for ($i = 1; $i <= $size; $i++) {
            $customers[] = sprintf(
                'Pelanggan Chaos %02d %s',
                $i,
                ucfirst($faker->firstName())
            );
        }

        return $customers;
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, mixed>
     */
    private function pickRandomSubset(array $items, int $count, Generator $faker): array
    {
        $pool = array_values($items);
        $target = min($count, count($pool));
        $picked = [];

        while (count($picked) < $target && count($pool) > 0) {
            $index = $faker->numberBetween(0, count($pool) - 1);
            $picked[] = $pool[$index];
            array_splice($pool, $index, 1);
        }

        return $picked;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function makeLineBlueprint(Generator $faker): array
    {
        $mode = $faker->numberBetween(1, 6);

        return match ($mode) {
            1 => $this->blueprintRetail($faker),
            2 => $this->blueprintServiceOnly($faker),
            3 => $this->blueprintServiceWithStock($faker),
            4 => $this->blueprintMixedRetailService($faker),
            5 => $this->blueprintServiceOutside($faker),
            default => $this->blueprintChaosMix($faker),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blueprintRetail(Generator $faker): array
    {
        $lines = [];
        $count = $faker->numberBetween(1, 4);

        for ($i = 0; $i < $count; $i++) {
            $lines[] = [
                'kind' => 'product_sale',
                'qty_min' => 1,
                'qty_max' => 3,
                'markup_min' => 0,
                'markup_max' => 25,
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blueprintServiceOnly(Generator $faker): array
    {
        return [[
            'kind' => 'service_fee',
            'amount_min' => 25_000,
            'amount_max' => 400_000,
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blueprintServiceWithStock(Generator $faker): array
    {
        $lines = [[
            'kind' => 'service_fee',
            'amount_min' => 30_000,
            'amount_max' => 350_000,
        ]];

        $count = $faker->numberBetween(1, 3);
        for ($i = 0; $i < $count; $i++) {
            $lines[] = [
                'kind' => 'service_product',
                'qty_min' => 1,
                'qty_max' => 2,
                'markup_min' => 0,
                'markup_max' => 20,
            ];
        }

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blueprintMixedRetailService(Generator $faker): array
    {
        $lines = $this->blueprintRetail($faker);
        $lines[] = [
            'kind' => 'service_fee',
            'amount_min' => 20_000,
            'amount_max' => 250_000,
        ];

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blueprintServiceOutside(Generator $faker): array
    {
        return [
            [
                'kind' => 'service_fee',
                'amount_min' => 40_000,
                'amount_max' => 300_000,
            ],
            [
                'kind' => 'outside_cost',
                'amount_min' => 10_000,
                'amount_max' => 250_000,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function blueprintChaosMix(Generator $faker): array
    {
        $lines = [
            [
                'kind' => 'service_fee',
                'amount_min' => 20_000,
                'amount_max' => 300_000,
            ],
        ];

        $retailCount = $faker->numberBetween(1, 2);
        for ($i = 0; $i < $retailCount; $i++) {
            $lines[] = [
                'kind' => 'product_sale',
                'qty_min' => 1,
                'qty_max' => 3,
                'markup_min' => 0,
                'markup_max' => 30,
            ];
        }

        if ($faker->boolean(60)) {
            $lines[] = [
                'kind' => 'service_product',
                'qty_min' => 1,
                'qty_max' => 2,
                'markup_min' => 0,
                'markup_max' => 20,
            ];
        }

        if ($faker->boolean(45)) {
            $lines[] = [
                'kind' => 'outside_cost',
                'amount_min' => 10_000,
                'amount_max' => 200_000,
            ];
        }

        return $lines;
    }

    /**
     * @param array<int, array<string, mixed>> $blueprint
     * @return array<int, array<string, mixed>>
     */
    private function materializeLines(array $blueprint, Generator $faker, ?CustomerTransaction $existingDraft = null): array
    {
        $inventoryRows = ProductInventory::query()
            ->join('products', 'products.id', '=', 'product_inventory.product_id')
            ->where('products.is_active', true)
            ->orderBy('product_inventory.product_id')
            ->get([
                'product_inventory.product_id',
                'product_inventory.on_hand_qty',
                'product_inventory.reserved_qty',
                'products.sale_price',
            ]);

        $availableByProduct = [];
        $salePriceByProduct = [];

        foreach ($inventoryRows as $row) {
            $productId = (int) $row->product_id;
            $availableByProduct[$productId] = (int) $row->on_hand_qty - (int) $row->reserved_qty;
            $salePriceByProduct[$productId] = (int) $row->sale_price;
        }

        if ($existingDraft) {
            $existingDraft->loadMissing('lines');

            foreach ($existingDraft->lines as $line) {
                if ($line->usesStock() && $line->product_id) {
                    $pid = (int) $line->product_id;
                    $availableByProduct[$pid] = (int) ($availableByProduct[$pid] ?? 0) + (int) $line->qty;
                }
            }
        }

        $lines = [];

        foreach ($blueprint as $intent) {
            $kind = (string) $intent['kind'];

            if (in_array($kind, ['product_sale', 'service_product'], true)) {
                $candidateIds = array_values(array_filter(
                    array_keys($availableByProduct),
                    fn ($productId) => (int) ($availableByProduct[$productId] ?? 0) > 0
                ));

                if (count($candidateIds) < 1) {
                    continue;
                }

                $productId = (int) $faker->randomElement($candidateIds);
                $available = (int) $availableByProduct[$productId];
                if ($available <= 0) {
                    continue;
                }

                $qtyWanted = $faker->numberBetween(
                    (int) ($intent['qty_min'] ?? 1),
                    (int) ($intent['qty_max'] ?? 1)
                );

                $qty = min($qtyWanted, $available);
                if ($qty <= 0) {
                    continue;
                }

                $salePrice = (int) ($salePriceByProduct[$productId] ?? 0);
                $baseAmount = $qty * $salePrice;

                $markupPercent = $faker->numberBetween(
                    (int) ($intent['markup_min'] ?? 0),
                    (int) ($intent['markup_max'] ?? 0)
                );

                $extra = (int) (round((($baseAmount * $markupPercent) / 100) / 1000) * 1000);
                $amount = max($baseAmount, $baseAmount + $extra);

                $lines[] = [
                    'kind' => $kind,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'amount' => $amount,
                    'note' => 'chaos-stock-line',
                ];

                $availableByProduct[$productId] = $available - $qty;
                continue;
            }

            $amount = (int) (round(
                $faker->numberBetween(
                    (int) ($intent['amount_min'] ?? 10_000),
                    (int) ($intent['amount_max'] ?? 100_000)
                ) / 1000
            ) * 1000);

            $lines[] = [
                'kind' => $kind,
                'product_id' => null,
                'qty' => null,
                'amount' => $amount,
                'note' => 'chaos-nonstock-line',
            ];
        }

        if (count($lines) < 1) {
            $lines[] = [
                'kind' => 'service_fee',
                'product_id' => null,
                'qty' => null,
                'amount' => 50_000,
                'note' => 'chaos-fallback-service',
            ];
        }

        return $lines;
    }

    private function executeSyntheticRefund(
        int $transactionId,
        string $refundedAt,
        Generator $faker,
        RefundCustomerTransactionUseCase $refundUseCase
    ): bool {
        /** @var CustomerTransaction|null $transaction */
        $transaction = CustomerTransaction::query()
            ->with('lines')
            ->find($transactionId);

        if (! $transaction || $transaction->status !== 'paid') {
            return false;
        }

        $alreadyRefunded = $transaction->refunded_at !== null
            || (int) $transaction->refund_amount > 0
            || $transaction->lines->contains(fn ($line) => (int) $line->refunded_qty > 0);

        if ($alreadyRefunded) {
            return false;
        }

        $refundableLines = $transaction->lines
            ->filter(fn ($line) => in_array($line->kind, ['product_sale', 'service_product'], true))
            ->filter(fn ($line) => ((int) $line->qty - (int) $line->refunded_qty) > 0)
            ->values();

        if ($refundableLines->count() < 1) {
            return false;
        }

        $selectedLines = $this->pickRandomSubset(
            $refundableLines->all(),
            $faker->numberBetween(1, $refundableLines->count()),
            $faker
        );

        $refundItems = [];
        $refundAmount = 0;

        foreach ($selectedLines as $line) {
            $maxQty = (int) $line->qty - (int) $line->refunded_qty;
            if ($maxQty <= 0) {
                continue;
            }

            $qtyRefund = $faker->numberBetween(1, $maxQty);
            $refundItems[] = [
                'line_id' => (int) $line->id,
                'qty' => (int) $qtyRefund,
            ];

            $unitRefundValue = (int) round(((int) $line->amount / max(1, (int) $line->qty)) / 1000) * 1000;
            $lineRefundAmount = max(1000, $unitRefundValue * $qtyRefund);
            $refundAmount += $lineRefundAmount;
        }

        if (count($refundItems) < 1 || $refundAmount <= 0) {
            return false;
        }

        $refundUseCase->execute([
            'transaction_id' => $transactionId,
            'refunded_at' => $refundedAt,
            'refund_amount' => $refundAmount,
            'items' => $refundItems,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function addSkip(
        array &$stats,
        string $reason,
        string $eventType,
        string $clientKey,
        string $message
    ): void {
        $stats['skip_total']++;
        $stats['skip_reasons'][$reason] = (int) ($stats['skip_reasons'][$reason] ?? 0) + 1;

        if (!isset($stats['skip_samples'][$reason])) {
            $stats['skip_samples'][$reason] = [];
        }

        if (count($stats['skip_samples'][$reason]) < 3) {
            $stats['skip_samples'][$reason][] = sprintf(
                '[%s][%s] %s',
                $eventType,
                $clientKey,
                $message
            );
        }
    }

    private function classifyExceptionReason(string $eventType, Throwable $e): string
    {
        $message = mb_strtolower(trim($e->getMessage()));

        if (str_contains($message, 'stok tidak cukup')) {
            return $eventType . '_stock_not_enough';
        }

        if (str_contains($message, 'reserved tidak cukup')) {
            return $eventType . '_reserved_not_enough';
        }

        if (str_contains($message, 'on hand tidak cukup')) {
            return $eventType . '_on_hand_not_enough';
        }

        if (str_contains($message, 'hanya draft yang boleh')) {
            return $eventType . '_not_draft';
        }

        if (str_contains($message, 'refund hanya untuk transaksi status paid')) {
            return $eventType . '_not_paid';
        }

        if (str_contains($message, 'refund untuk transaksi ini hanya boleh sekali')) {
            return $eventType . '_already_refunded';
        }

        if (str_contains($message, 'line tidak ditemukan')) {
            return $eventType . '_line_missing';
        }

        if (str_contains($message, 'line bukan stok')) {
            return $eventType . '_line_not_stock';
        }

        if (str_contains($message, 'refund melebihi qty line')) {
            return $eventType . '_refund_qty_exceeded';
        }

        if (str_contains($message, 'sale_unit_cost belum ada')) {
            return $eventType . '_sale_unit_cost_missing';
        }

        if (str_contains($message, 'minimal 1 line item')) {
            return $eventType . '_no_lines';
        }

        if (str_contains($message, 'minimal 1 line untuk refund')) {
            return $eventType . '_no_refund_items';
        }

        if (str_contains($message, 'qty wajib > 0 untuk line stok')) {
            return $eventType . '_qty_invalid';
        }

        if (str_contains($message, 'product_id wajib untuk line stok')) {
            return $eventType . '_product_missing';
        }

        if (str_contains($message, 'amount tidak boleh negatif')) {
            return $eventType . '_amount_negative';
        }

        if (str_contains($message, 'inventory tidak ditemukan')) {
            return $eventType . '_inventory_missing';
        }

        return $eventType . '_exception_other';
    }
}
