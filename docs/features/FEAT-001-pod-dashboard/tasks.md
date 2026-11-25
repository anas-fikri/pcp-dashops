# FEAT-001 · Tasks

Urutan: skema → domain → kontrak → wiring → UI → tes hijau. Setiap task selesai bila tes yang disebut lulus.

- [ ] **T1 Migrasi kolom baru** `pod_shipments` (§2) + rollback. *Done:* `php artisan migrate` & `migrate:rollback` bersih; test skema.
- [ ] **T2 Tabel `work_calendar`** + seeder (Sen–Sab default, libur nasional kosong). *Done:* test selisih HK = prototype untuk 20 pasangan tanggal.
- [ ] **T3 Eskalasi master** `coverage_areas` & SLA customer → keputusan arsitektur. *Done:* keputusan tercatat; importer dari `build_branch_master.py` diporting ke artisan command bila disetujui.
- [ ] **T4 `PodFilter`/`PodFilterRequest`** param baru + validasi (§3). *Done:* test 422 untuk nilai di luar whitelist & rentang > 366 hari.
- [ ] **T5 `AgingSql`** umur HK dari basis, warna relatif SLA. *Done:* test batas warna SLA 6 & 7, basis POS vs Delivered.
- [ ] **T6 Service:** `totals`, `trend`, Rp di semua agregat. *Done:* test invarian n & v (summary = awb list) di semua node/bucket/warna/minggu.
- [ ] **T7 `Api\PodController`** + resource JSON + route `/api/pod/*` (auth placeholder). *Done:* feature test tiap endpoint, bentuk respons sesuai §3.
- [ ] **T8 Export** kolom & sheet baru. *Done:* test baca XLSX: sheet, kolom, total Rp = summary.
- [ ] **T9 UI** porting prototype v8 ke Blade + JS memakai API (lihat `ui-design.md`). *Done:* e2e Playwright prototype (83 cek) dijalankan ulang terhadap aplikasi.
- [ ] **T10 Auth + RBAC cabang** (sebelum produksi). *Done:* user cabang hanya melihat cabangnya.
