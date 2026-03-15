<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateNoteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_note_handler_stores_new_note(): void
    {
        $this->loginAsKasir();
        $handler = app(CreateNoteHandler::class);

        $result = $handler->handle(
            'Budi Santoso',
            '2026-03-14',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseCount('notes', 1);

        $this->assertDatabaseHas('notes', [
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 0,
        ]);
    }

    public function test_create_note_handler_rejects_blank_customer_name(): void
    {
        $this->loginAsKasir();
        $handler = app(CreateNoteHandler::class);

        $result = $handler->handle(
            '   ',
            '2026-03-14',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_create_note_handler_rejects_invalid_transaction_date(): void
    {
        $this->loginAsKasir();
        $handler = app(CreateNoteHandler::class);

        $result = $handler->handle(
            'Budi Santoso',
            '14-03-2026',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());

        $this->assertDatabaseCount('notes', 0);
    }
}
