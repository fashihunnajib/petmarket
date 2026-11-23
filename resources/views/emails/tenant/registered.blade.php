<x-mail::message>
# Halo, {{ $owner->name }}!

Terima kasih telah mendaftarkan toko Anda di **PetMarket**. Data pendaftaran toko Anda telah kami terima dan saat ini sedang dalam proses peninjauan oleh tim Admin.

<x-mail::panel>
**Detail Pendaftaran Toko:**
- **Nama Toko:** {{ $tenant->name }}
- **Kode Toko:** {{ $tenant->code }}
- **URL Toko:** {{ url('/toko/'.$tenant->slug) }}
- **Status Saat Ini:** Menunggu Verifikasi (`{{ strtoupper((string) $tenant->status) }}`)
</x-mail::panel>

Anda akan dapat mulai mengelola produk dan menerima pesanan segera setelah Admin mengaktifkan toko Anda.

<x-mail::button :url="url('/dashboard')">
Buka Dashboard Akun
</x-mail::button>

Salam hangat,<br>
Tim **{{ config('app.name', 'PetMarket') }}**
</x-mail::message>