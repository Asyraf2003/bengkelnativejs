<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Audit\NullAuditLogAdapter;
use App\Adapters\Out\Auth\LaravelUuidAdapter;
use App\Adapters\Out\Clock\SystemClockAdapter;
use App\Adapters\Out\Persistence\DatabaseTransactionManagerAdapter;
use App\Adapters\Out\Policy\NullCapabilityPolicyAdapter;
use App\Application\System\Health\HealthCheckHandler;
use App\Ports\In\HealthCheckUseCase;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\CapabilityPolicyPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Illuminate\Support\ServiceProvider;

class HexagonalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HealthCheckUseCase::class, HealthCheckHandler::class);

        $this->app->singleton(ClockPort::class, SystemClockAdapter::class);
        $this->app->singleton(UuidPort::class, LaravelUuidAdapter::class);
        $this->app->singleton(AuditLogPort::class, NullAuditLogAdapter::class);
        $this->app->singleton(CapabilityPolicyPort::class, NullCapabilityPolicyAdapter::class);
        $this->app->singleton(TransactionManagerPort::class, DatabaseTransactionManagerAdapter::class);
    }
}
