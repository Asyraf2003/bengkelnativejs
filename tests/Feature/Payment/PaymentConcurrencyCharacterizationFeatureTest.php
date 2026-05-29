<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;
use Throwable;

final class PaymentConcurrencyCharacterizationFeatureTest extends TestCase
{
    use SeedsMinimalNotePaymentFixture;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);

        parent::tearDown();
    }

    public function test_concurrent_full_payments_on_same_note_do_not_over_allocate_outstanding(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::fail('Payment concurrency characterization requires pcntl_fork(). Current PHP runtime cannot prove true concurrent request behavior.');
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            self::fail('Payment concurrency characterization requires a row-locking database driver such as mysql, mariadb, or pgsql. SQLite does not prove lockForUpdate() same-note serialization.');
        }

        $this->seedPaymentConcurrencyNote();

        $workDir = storage_path('framework/testing/payment-concurrency-' . (string) Str::uuid());
        $startFile = $workDir . '/start';

        if (!is_dir($workDir) && !mkdir($workDir, 0775, true) && !is_dir($workDir)) {
            self::fail('Failed to create payment concurrency work directory.');
        }

        DB::disconnect();

        $children = [];

        for ($worker = 1; $worker <= 2; $worker++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                self::fail('Failed to fork payment concurrency worker.');
            }

            if ($pid === 0) {
                self::runPaymentWorker($worker, $workDir, $startFile);
                exit(0);
            }

            $children[] = $pid;
        }

        touch($startFile);

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                self::fail('Payment concurrency worker exited abnormally.');
            }
        }

        DB::reconnect();

        $results = [];

        for ($worker = 1; $worker <= 2; $worker++) {
            $resultFile = $workDir . '/worker-' . $worker . '.json';

            self::assertFileExists($resultFile, 'Payment concurrency worker did not write a result file.');

            $decoded = json_decode((string) file_get_contents($resultFile), true, 512, JSON_THROW_ON_ERROR);

            self::assertIsArray($decoded);
            $results[] = $decoded;
        }

        $allocatedTotal = (int) DB::table('payment_component_allocations')
            ->where('note_id', 'note-concurrency-1')
            ->sum('allocated_amount_rupiah');

        self::assertLessThanOrEqual(
            100000,
            $allocatedTotal,
            'Concurrent full payments must not over-allocate the same note outstanding.'
        );

        self::assertSame(
            100000,
            $allocatedTotal,
            'Exactly one full outstanding allocation should be committed for the note.'
        );

        $paymentIds = DB::table('customer_payments')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $allocatedPaymentIds = DB::table('payment_component_allocations')
            ->select('customer_payment_id')
            ->distinct()
            ->pluck('customer_payment_id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        foreach ($paymentIds as $paymentId) {
            self::assertContains(
                $paymentId,
                $allocatedPaymentIds,
                'A committed customer payment must not be left without component allocation.'
            );
        }

        self::assertGreaterThanOrEqual(
            1,
            count(array_filter($results, static fn (array $result): bool => ($result['success'] ?? false) === true)),
            'At least one concurrent payment worker should succeed.'
        );
    }

    private static function runPaymentWorker(int $worker, string $workDir, string $startFile): void
    {
        try {
            DB::purge();
            DB::reconnect();

            $deadline = microtime(true) + 10.0;

            while (!file_exists($startFile)) {
                if (microtime(true) > $deadline) {
                    throw new \RuntimeException('Timed out waiting for payment concurrency start signal.');
                }

                usleep(1000);
            }

            $result = app(RecordAndAllocateNotePaymentHandler::class)->handle(
                'note-concurrency-1',
                100000,
                '2026-05-29',
            );

            file_put_contents(
                $workDir . '/worker-' . $worker . '.json',
                json_encode([
                    'worker' => $worker,
                    'success' => $result->isSuccess(),
                    'message' => $result->message(),
                ], JSON_THROW_ON_ERROR)
            );
        } catch (Throwable $e) {
            file_put_contents(
                $workDir . '/worker-' . $worker . '.json',
                json_encode([
                    'worker' => $worker,
                    'success' => false,
                    'exception_class' => $e::class,
                    'exception_message' => $e->getMessage(),
                ], JSON_THROW_ON_ERROR)
            );
        } finally {
            DB::disconnect();
        }
    }

    private function seedPaymentConcurrencyNote(): void
    {
        $this->seedNoteBase('note-concurrency-1', 'Budi Concurrent Payment', '2026-05-29', 100000, 'open');

        $this->seedWorkItemBase(
            'wi-concurrency-1',
            'note-concurrency-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        $this->seedServiceDetailBase(
            'wi-concurrency-1',
            'Servis Concurrent Payment',
            100000,
            ServiceDetail::PART_SOURCE_NONE,
        );
    }
}
