<?php

namespace App\Modules\Ordering\Exceptions;

use RuntimeException;

final class CheckoutException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('Keranjang atau pilihan produk kosong.');
    }

    public static function cartNotOrderable(): self
    {
        return new self('Ada produk yang tidak dapat dipesan. Perbarui keranjang lalu coba lagi.');
    }

    public static function insufficientStock(): self
    {
        return new self('Stok produk berubah saat checkout. Perbarui keranjang lalu coba lagi.');
    }

    public static function tenantClosed(string $tenantName): self
    {
        return new self("{$tenantName} sedang tutup. Hapus produknya atau jadwalkan waktu ambil (Pre-Order).");
    }

    public static function pickupNeedsSchedule(): self
    {
        return new self('Pilih waktu pengambilan untuk pesanan terjadwal.');
    }

    public static function commissionNotConfigured(): self
    {
        return new self('Konfigurasi komisi toko belum tersedia.');
    }
}
