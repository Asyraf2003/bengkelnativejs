<?php

declare(strict_types=1);

namespace App\Core\ProductCatalog\Product;

use App\Core\Shared\ValueObjects\Money;

trait ProductState
{
    private function __construct(
        private string $id,
        private ?string $kodeBarang,
        private string $namaBarang,
        private string $merek,
        private ?int $ukuran,
        private Money $hargaJual,
    ) {}

    public function id(): string { return $this->id; }
    public function kodeBarang(): ?string { return $this->kodeBarang; }
    public function namaBarang(): string { return $this->namaBarang; }
    public function merek(): string { return $this->merek; }
    public function ukuran(): ?int { return $this->ukuran; }
    public function hargaJual(): Money { return $this->hargaJual; }
}
