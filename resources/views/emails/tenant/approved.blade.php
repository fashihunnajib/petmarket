<x-mail::message>
# Selamat, {{ $ownerName }}!

Pengajuan toko Anda **{{ $tenant->name }}** (`{{ $tenant->code }}`) telah disetujui dan diaktifkan oleh Admin **PetMarket**.

Anda sekarang sudah dapat masuk ke Dashboard Toko untuk menambahkan produk dan mulai menerima pesanan.

<x-mail::button :url="url('/dashboard')">
Masuk ke Dashboard Toko
</x-mail::button>

Salam hangat,<br>
Tim **{{ config('app.name', 'PetMarket') }}**
</x-mail::message>