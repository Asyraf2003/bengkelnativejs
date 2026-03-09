<?php

declare(strict_types=1);

namespace Tests\Arch;

use PHPUnit\Framework\TestCase;

final class HexagonalDependencyTest extends TestCase
{
    public function test_hexagonal_audit_script_passes(): void
    {
        $command = PHP_BINARY . ' scripts/audit-hex.php 2>&1';

        $output = [];
        $exitCode = 1;

        exec($command, $output, $exitCode);

        $this->assertSame(
            0,
            $exitCode,
            "Hexagonal audit failed:\n" . implode("\n", $output)
        );

        $this->assertContains('HEXAGONAL AUDIT: OK', $output);
    }
}
