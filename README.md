# PCP DashOps — Dashboard Performance POD

Dashboard untuk memonitor pengembalian POD (Proof of Delivery). Alurnya dari summary sampai detail AWB:
KPI → pohon status (Balik / Belum Balik / Sudah Balik × Intracity / Domestik) → aging per 5 hari →
breakdown per cabang/perwakilan dan customer → daftar AWB → export Excel.

Spesifikasi, aturan bisnis, dan asumsi: **[docs/pod-dashboard-spec.md](docs/pod-dashboard-spec.md)**.

## Stack

- Laravel 13, PHP ≥ 8.3, Blade (tanpa build step frontend)
- DB: SQLite (dev/test); SQL Server, MySQL, atau PostgreSQL untuk produksi
- Export Excel: `openspout/openspout` (streaming, hemat memori)

## Menjalankan lokal (dengan data demo sintetis)

```bash
composer install
```

```bash
cp .env.example .env && php artisan key:generate
```

```bash
touch database/database.sqlite && php artisan migrate --seed
```

```bash
php artisan serve
```

Buka http://127.0.0.1:8000/pod. Seeder membuat 8.000 AWB **fiktif** (`PodShipmentSeeder`).

## Test

```bash
php artisan test
```

Test mencakup: batas bucket aging, ambang warna, perhitungan lead time dan SLA, filter,
validasi input, export Excel, serta invarian **angka summary = jumlah baris detail**.

## Struktur penting

| Path | Isi |
|---|---|
| `config/pod.php` | Bucket aging, warna, SLA, batas export |
| `app/Enums/PodStatus.php`, `PodNode.php` | Status POD dan pohon drill-down |
| `app/Services/Pod/PodDashboardService.php` | Semua query agregasi dan detail |
| `app/Services/Pod/AgingSql.php` | Ekspresi umur/bucket/warna lintas driver DB |
| `app/Services/Pod/PodExcelExport.php` | Workbook "Performance POD" |
| `app/Http/Requests/PodFilterRequest.php` | Whitelist parameter filter |
| `resources/views/pod/*` | Tampilan summary dan detail |

## Sebelum produksi

Lihat checklist di spesifikasi §7. Yang paling penting: **pasang autentikasi + RBAC** dan
**sambungkan `pod_shipments` ke data operasional** (ETL atau VIEW, §6).
