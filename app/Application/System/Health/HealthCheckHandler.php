<?php

declare(strict_types=1);

namespace App\Application\System\Health;

use App\Application\Shared\DTO\Result;
use App\Ports\In\HealthCheckUseCase;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;

final class HealthCheckHandler implements HealthCheckUseCase
{
    public function __construct(
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
    ) {
    }

    public function execute(): Result
    {
        return Result::success([
            'status' => 'ok',
            'time' => $this->clock->now()->format(DATE_ATOM),
            'request_id' => $this->uuid->generate(),
        ]);
    }
}
