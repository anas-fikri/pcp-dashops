# PCP DashOps — catatan untuk agent

- Laravel 13 app: Dashboard Performance POD. Spesifikasi & asumsi bisnis: `docs/pod-dashboard-spec.md`.
- Aturan bisnis (bucket aging, warna, SLA) hanya di `config/pod.php`; jangan hard-code di query/view.
- Semua query di `app/Services/Pod/PodDashboardService.php`; ekspresi tanggal harus lewat `AgingSql` (portabel sqlite/sqlsrv/mysql/pgsql).
- Setiap angka summary wajib bisa direproduksi oleh `detailQuery()` — dijaga oleh `tests/Feature/PodDashboardTest.php`.
- Jalankan `php artisan test` dan `./vendor/bin/pint` sebelum commit.
- UI: ikuti `docs/design.md` + `docs/tokens.json` (satu warna = satu fungsi; setiap angka AWB disertai Rp). Layar: `docs/ux-foundations.md` (SCR-DSH-*), spesifikasi & kontrak: `docs/features/FEAT-001-pod-dashboard/`. Prototype acuan: `prototype/pod-dashboard.html`.
