# FEAT-001 · POD Performance Dashboard — Technical Design

> Mode tereduksi: belum ada `docs/srs.md`, `docs/use-cases.md`, `docs/architecture.md`, `docs/implementation-plan.md`.
> Tidak ada ID FR/UC yang dikutip (tidak dikarang). Kebutuhan bersumber dari `docs/pod-dashboard-spec.md` dan keputusan user di sesi desain.
> Desain mengikuti kode yang sudah ada: `PodDashboardService`, `PodFilterRequest`, `AgingSql`, route prefix `/pod`.

## 1. Keadaan kode saat ini (dasar desain)
| Ada | Perlu diubah agar sesuai prototype v8 |
|---|---|
| Filter tanggal = `shipment_date` | Ganti ke **`pos_date`** |
| Umur dari delivered, hari kalender | Hari kerja + pilihan basis **Delivered / POS** |
| SLA per scope (5/10) | SLA **per customer** (6/7 HK), warna relatif SLA |
| `dest_city_tier` | `dest_branch_level` (BRANCH/SUB/SITE) dari master coverage |
| `dest_branch_type` PCP/PERWAKILAN | `dest_office_type` (Cabang/Satelit/Agen/Perwakilan) → grup INTERNAL/AGENT |
| Tanpa nilai | `freight_amount` per AWB; setiap agregat jumlah juga mengembalikan Rp |
| Halaman Blade | JSON API untuk UI interaktif (Blade lama tetap sampai UI baru siap) |

## 2. Skema (migrasi pada tabel `pod_shipments` yang ada)
```php
Schema::table('pod_shipments', function (Blueprint $t) {
    $t->date('pos_date')->after('pod_status')->index();           // dasar filter periode
    $t->string('dest_area_code', 12)->nullable()->index();        // CityCode area tujuan
    $t->string('dest_branch_level', 6)->nullable()->index();      // BRANCH|SUB|SITE
    $t->string('dest_office_type', 12)->nullable();               // Cabang|Satelit|Agen|Perwakilan
    $t->string('dest_region', 20)->nullable()->index();
    $t->string('origin_branch_code', 3)->nullable();
    $t->unsignedTinyInteger('pod_sla_days')->nullable();          // salinan SLA customer saat transaksi
    $t->decimal('freight_amount', 14, 2)->default(0);
    $t->decimal('chargeable_weight', 8, 2)->nullable();
    $t->string('stpod_no', 30)->nullable()->index();
    $t->string('podp_no', 30)->nullable()->index();
    $t->index(['pos_date', 'pod_required', 'pod_status']);
});
```
Rollback: `dropColumn` untuk semua kolom di atas (data lama tidak tersentuh).

**Eskalasi (perlu keputusan arsitektur, tidak dirancang lokal):**
- Master **coverage area** (90 ribu area, sumber tier/office/region) dan master **customer SLA** adalah entitas baru. Usulan: tabel `coverage_areas` & `customers.pod_sla_days`, disinkron dari sistem sumber. Dicatat sebagai amandemen arsitektur yang harus disetujui.

## 3. Kontrak API
Semua: `GET`, auth wajib (sebelum produksi), respons `{"data": …, "meta": {"as_of": "2026-11-25", "filters": {…}}}`.
Error validasi: `422 {"message": "...", "errors": {field: [..]}}` (default Laravel). Nilai uang dalam rupiah bulat (integer).

### Parameter filter bersama (`PodFilterRequest`, whitelist)
| Param | Tipe | Aturan |
|---|---|---|
| `preset` | int | 30\|60\|90\|120\|180 — atau gunakan `pos_from`+`pos_to` |
| `pos_from`, `pos_to` | date Y-m-d | `pos_from ≤ pos_to ≤ as_of`, rentang ≤ 366 hari |
| `basis` | enum | `delivered` (default) \| `pos` |
| `customer` | string ≤20 | alpha_dash |
| `scope` | enum | INTRACITY\|DOMESTIK |
| `region` | string ≤20 | dari master |
| `office_group` | enum | INTERNAL\|AGENT |
| `level` | enum | BRANCH\|SUB\|SITE |
| `branch` | string 3 | kode cabang tujuan |
| `workweek` | enum | `mon_sat` (default) \| `mon_fri` |

### Endpoint
| Method & path | Untuk layar | Respons `data` |
|---|---|---|
| `GET /api/pod/summary` | SCR-DSH-001 | `totals{all{n,v}, balik{n,v}, tidak{n,v}}`, `kpi{sudah, belum, past_sla, on_time, avg_wd}` (masing-masing `{n,v,pct}`), `health{level, reasons[]}`, `focus{branch, customer, status}` |
| `GET /api/pod/trend` | SCR-DSH-001 | `weeks[{week_start, n, returned{n,v}, not_returned{n,v}, on_time_pct|null}]` (`on_time_pct` null bila returned < 10) |
| `GET /api/pod/status-tree` | SCR-DSH-001 | `nodes[{id, parent, n, v, pct_parent, pct_return, scope{INTRACITY{n,v},DOMESTIK{n,v}}, colours{hitam{n,v},…}}]` |
| `GET /api/pod/aging?metric=n\|v&mode=bucket\|colour` | SCR-DSH-001 | `rows[{node, cells[{key, n, v, dominant_colour}], total{n,v}}]` |
| `GET /api/pod/performance` | SCR-DSH-001 | `groups[{key, returned{n,v}, avg_wd, on_time_pct, past_sla{n,v}}]` |
| `GET /api/pod/ranking?dim=branch\|customer\|level\|region&sort=n\|v&limit=10` | SCR-DSH-001 | `rows[{id, name, sub, belum{n,v}, share_pct, colours{…}, past_sla{n,v}, return_pct, on_time_pct}]` |
| `GET /api/pod/awb?node=&colour=&bucket=&week=&podp=&stpod=&q=&sort=&dir=&page=` | SCR-DSH-002 | paginated `items[{awb, customer, origin, dest_branch, level, office_type, region, scope, status, pos_date, delivered_at, returned_at, age_wd, colour, sla_days, past_sla, freight_amount, stpod_no, podp_no, note}]` + `stats{n, v, avg_wd, past{n,v}, contributors{branch[],customer[],status[]}}` |
| `GET /api/pod/awb/{awb}` | SCR-DSH-003 | AWB lengkap + `journey[]` + `related{podp{n,v}, stpod{n,v}, branch_outstanding{n,v}, customer_outstanding{n,v}}` |
| `GET /api/pod/export?…` | SCR-DSH-005 | XLSX streaming (Summary, Aging, Aging (Rp), Branch, Customer, AWB); `throttle:10,1` |
| `GET /api/pod/options` | filter | customers (dengan SLA), regions, branches (dengan level & office group) |

**Invarian (wajib diuji):** setiap `n` di summary/tree/aging/ranking/trend = `total` dari `/api/pod/awb` dengan saringan yang sama; setiap `v` = Σ `freight_amount` baris yang sama.

## 4. Komponen
| Komponen | Tanggung jawab |
|---|---|
| `PodFilter` / `PodFilterRequest` | + `pos_from/to`, `basis`, `region`, `office_group`, `level`, `workweek` |
| `WorkingDays` (baru) | Kalender hari kerja (Sen–Sab/Sen–Jum, libur nasional dari tabel) → ekspresi SQL per driver atau tabel kalender `work_calendar(date, is_workday, cum)` untuk selisih HK via join (portabel & cepat) |
| `AgingSql` | Umur = `cum(to) − cum(from)` dengan `from` = POS atau delivered sesuai basis; warna relatif `pod_sla_days` |
| `PodDashboardService` | + `totals()`, `trend()`, nilai Rp di semua agregat |
| `Api\PodController` (baru) | Endpoint di atas; resource JSON |
| `PodExcelExport` | Kolom baru & sheet Aging (Rp) |

## 5. Keputusan desain
- **D1 Kalender hari kerja sebagai tabel** (`work_calendar` dengan kumulatif HK) daripada menghitung di PHP: selisih HK = selisih kumulatif, satu join, portabel lintas SQL Server/MySQL/Postgres. Driver: performa & libur nasional.
- **D2 SLA disalin ke baris (`pod_sla_days`)** saat transaksi: perubahan kontrak tidak mengubah histori.
- **D3 JSON API + front-end dari prototype**: prototype v8 menjadi acuan UI; Blade lama dipertahankan sampai paritas.

## 6. Kriteria penerimaan (sumber: spec & keputusan user; tanpa ID FR)
1. Periode (preset atau rentang) menyaring berdasarkan `pos_date`.
2. Basis umur Delivered/POS mengubah umur, aging, warna, avg, Lewat SLA, Tepat Waktu secara konsisten; belum terkirim selalu dari POS.
3. Warna umur relatif SLA customer: hitam ≤ s−3, oranye s−2..s−1, merah s..s+3, ungu ≥ s+4.
4. Total = POD balik + tidak balik (jumlah & Rp).
5. Setiap jumlah AWB disertai Rp; invarian §3 berlaku untuk semua endpoint.
6. Trend: `on_time_pct` null bila returned < 10.
7. Export memuat kolom POS, nilai, berat, dan sheet Aging (Rp).
