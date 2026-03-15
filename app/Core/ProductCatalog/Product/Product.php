<?php

declare(strict_types=1);

namespace App\Core\ProductCatalog\Product;

use App\Core\Shared\ValueObjects\Money;

final class Product
{
    use ProductState;
    use ProductValidation;

    public static function create(
        string $id, ?string $kode, string $nama, string $merek, ?int $ukuran, Money $harga
    ): self {
        self::assertValid($id, $nama, $merek, $harga);

        return new self(
            $id, self::normalizeKode($kode), trim($nama), trim($merek), $ukuran, $harga
        );
    }

    public static function rehydrate(
        string $id, ?string $kode, string $nama, string $merek, ?int $ukuran, Money $harga
    ): self {
        self::assertValid($id, $nama, $merek, $harga);

        return new self(
            $id, self::normalizeKode($kode), trim($nama), trim($merek), $ukuran, $harga
        );
    }

    public function updateMaster(
        ?string $kode, string $nama, string $merek, ?int $ukuran, Money $harga
    ): void {
        self::assertValid($this->id, $nama, $merek, $harga);

        $this->kodeBarang = self::normalizeKode($kode);
        $this->namaBarang = trim($nama);
        $this->merek = trim($merek);
        $this->ukuran = $ukuran;
        $this->hargaJual = $harga;
    }
}
