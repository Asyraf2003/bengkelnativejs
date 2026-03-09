<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Shared\DTO;

use App\Application\Shared\DTO\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function test_success_result_contains_expected_data(): void
    {
        $result = Result::success([
            'status' => 'ok',
        ], 'done');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertSame(['status' => 'ok'], $result->data());
        $this->assertSame('done', $result->message());
        $this->assertSame([], $result->errors());
    }

    public function test_failure_result_contains_expected_error_payload(): void
    {
        $result = Result::failure('validation failed', [
            'field' => ['required'],
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertNull($result->data());
        $this->assertSame('validation failed', $result->message());
        $this->assertSame([
            'field' => ['required'],
        ], $result->errors());
    }

    public function test_to_array_returns_consistent_envelope(): void
    {
        $result = Result::success(['x' => 1], 'ok');

        $this->assertSame([
            'success' => true,
            'data' => ['x' => 1],
            'message' => 'ok',
            'errors' => [],
        ], $result->toArray());
    }
}
