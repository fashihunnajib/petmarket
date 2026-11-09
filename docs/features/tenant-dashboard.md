# 🚀 Feature: Tenant Dashboard & Multi-Tenant Routing

## 📌 Deskripsi Fitur
Mengintegrasikan tampilan Dashboard Tenant dengan data real database (KPI Statistik, Saldo, Produk Aktif, Pesanan Terbaru) dan mengatur alur autentikasi multi-tenant berbasis slug URL (`/tenant/{slug}/dashboard`).

## 🗂️ Perubahan File (Changed Files)
- **Controller**: `app/Http/Controllers/Tenant/DashboardController.php` (Menangani query KPI & pesanan)
- **Routing**: `routes/web.php` & `routes/tenant.php` (Handling slug tenant & middleware `auth`)
- **Auth Provider**: `app/Providers/FortifyServiceProvider.php` (Redirect akun role tenant setelah login)
- **Views**: 
  - `resources/views/tenant/dashboard.blade.php` (UI dashboard)
  - `resources/views/components/layouts/tenant.blade.php` (Sidebar link & prop `@props`)
- **Model**: `app/Models/User.php` (Relasi `tenant()` & generic type PHPDoc)

## 🔑 Database Mapping
| Fitur / KPI | Tabel Reference | Kolom yang Digunakan |
| :--- | :--- | :--- |
| **User Tenant Link** | `user_tenant_roles` | `user_id`, `tenant_id` |
| **Saldo Cair Toko** | `tenant_balances` | `available_amount` |
| **Produk Aktif** | `products` | `is_available` |
| **Pendapatan Toko** | `tenant_orders` | `grand_total_amount - commission_amount` |

## 🧪 Cara Pengujian (Testing)
1. Login dengan akun ber-role `tenant`.
2. Pastikan halaman otomatis ter-redirect ke `http://localhost:8000/tenant/demo/dashboard`.
3. Verifikasi angka KPI di card sesuai dengan data di MySQL.
4. Jalankan static analysis:
   ```bash
   vendor/bin/phpstan analyze --memory-limit=-1