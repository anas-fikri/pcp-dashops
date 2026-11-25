# Spesifikasi Dashboard Performance POD

Dokumen ini menerjemahkan sketsa catatan "POD" menjadi aturan yang bisa diimplementasikan.
Semua keputusan untuk bagian yang ambigu ditandai **[ASUMSI]**. Asumsi ini perlu divalidasi
oleh tim operasional/POD sebelum go-live. Nilai yang bisa berubah ada di `config/pod.php`.

## 1. Tujuan

- Memantau **POD yang wajib kembali (POD Balik)**: berapa yang sudah kembali, berapa yang belum, dan sudah berapa lama.
- Menunjukkan **siapa yang harus ditindaklanjuti**: cabang/perwakilan tujuan dan customer dengan backlog atau overdue terbesar.
- Mengukur **performa pengembalian**: return rate, rata-rata lead time, dan persentase on-time terhadap SLA.
- Setiap angka bisa **di-drill sampai level AWB** dan **diekspor ke Excel**.

## 2. Pohon status (sesuai sketsa)

```
POD
├── Tidak Balik                  pod_required = false
└── Balik                        pod_required = true
    ├── Belum Balik
    │   ├── Not Yet Delivered    NOT_DELIVERED
    │   └── Delivered            (paket terkirim, POD fisik belum kembali)
    │       ├── Masih di Pelanggan   MASIH_DI_PELANGGAN
    │       ├── PODC                 PODC
    │       ├── Redoc                REDOC
    │       └── Upload               UPLOAD
    └── Sudah Balik
        ├── STPOD                STPOD
        ├── PODP                 PODP
        └── Arsip                ARSIP
```

**Intracity / Domestik** di sketsa digambar bersilangan ke Belum Balik dan Sudah Balik.
Artinya keduanya **dimensi pemisah**, bukan level hierarki. Di dashboard, keduanya tampil sebagai
kolom pada pohon status dan sebagai filter.

**Arti status** (✅ = dikonfirmasi user bisnis 25 Nov 2026):

| Kode | Arti |
|---|---|
| Masih di Pelanggan | [ASUMSI] POD fisik masih ditahan penerima (menunggu cap/tanda tangan) |
| PODC ✅ | Proof of Delivery Centralize: POD diurus secara tersentralisasi |
| Redoc ✅ | Redocumentation: POD didokumentasikan ulang |
| Upload | [ASUMSI] Soft copy POD sudah di-upload, fisik belum diserahterimakan |
| STPOD ✅ | Serah Terima Proof of Delivery |
| PODP ✅ | Nomor resi yang memuat banyak POD untuk dikirim ke pelanggan (satu PODP → banyak AWB) |
| Arsip | [ASUMSI] POD sudah diarsipkan |

Implikasi data: tabel kanonik perlu kolom `stpod_no` dan `podp_no` supaya satu resi PODP bisa ditelusuri ke AWB-AWB di dalamnya.

## 3. Aturan aging

| Kondisi | Umur dihitung dari | Makna |
|---|---|---|
| Belum Balik, Not Yet Delivered | `as_of − shipment_date` | umur kiriman yang belum terkirim |
| Belum Balik, Delivered (4 status) | `as_of − delivered_at` | umur POD outstanding sejak paket diterima |
| Sudah Balik | `pod_returned_at − delivered_at` | lead time pengembalian POD |
| Tidak Balik | – | tidak di-aging |

- Satuan: **hari kerja (HK)** ✅. Hari kirim/delivered = hari ke-0. **[ASUMSI]** Hari kerja Senin–Jumat (prototype punya pilihan Senin–Sabtu); libur nasional belum diperhitungkan dan perlu tabel kalender libur.
- **Bucket "per 5 hari × 6":** 0–5, 6–10, 11–15, 16–20, 21–25, 26–30, ditambah **>30** supaya tidak ada data yang hilang.
- **Warna severity relatif terhadap SLA customer** ✅ (s = 6 atau 7 HK):

| Warna | Arti | Rumus | SLA 6 HK | SLA 7 HK |
|---|---|---|---|---|
| Hitam | Aman | 0 … s−3 | 0–3 | 0–4 |
| Oranye | Mendekati SLA | s−2 … s−1 | 4–5 | 5–6 |
| Merah | Jatuh tempo / lewat SLA | s … s+3 | 6–9 | 7–10 |
| Ungu | Kritis, eskalasi (= overdue) | ≥ s+4 | ≥10 | ≥11 |

  Untuk SLA 6 HK hasilnya sama dengan catatan asli (gap hari ke-9 masuk merah).
- **"Sort"** di sketsa diterjemahkan sebagai urutan default daftar detail: **umur terlama di atas**. Semua kolom utama bisa diurutkan ulang.

## 4. KPI dan SLA

| KPI | Rumus |
|---|---|
| Total POD Balik | COUNT(pod_required = true) |
| Return rate | Sudah Balik / POD Balik |
| Belum Balik | Not Yet Delivered + Delivered |
| Overdue | Belum Balik dengan umur ≥ 10 hari (ungu) |
| Rata-rata lead time | AVG(pod_returned_at − delivered_at) untuk Sudah Balik |
| On-time return | Sudah Balik dengan lead time ≤ SLA scope |

**SLA kembali POD ✅:** ditentukan **per pelanggan**, 6 atau 7 hari kerja sejak delivered.
Perlu kolom `sla_days` di master customer. "Lewat SLA" = POD delivered yang belum kembali dengan umur > SLA pelanggan.
Catatan: aplikasi Laravel saat ini masih memakai SLA per scope (5/10 hari kalender); akan disesuaikan setelah UX prototype disetujui.

## 5. Alur layar (summary → detail)

> Prototype UI/UX interaktif (data simulasi): `prototype/pod-dashboard.html`. Detail dibuka lewat **modal**, bukan pindah halaman.
> Target: dashboard bisa dipahami < 1 menit, lewat kalimat "Ringkasan 1 menit" + status kesehatan + 5 KPI berwarna.


1. **Summary** (`/pod`)
   - Filter global: periode tanggal kirim (default 90 hari terakhir), Customer, PCP/Perwakilan, Cabang, Tier kota, Scope.
   - 6 kartu KPI, chip distribusi warna, Pohon Status × Intracity/Domestik, Performa Pengembalian, matriks Aging Belum Balik, matriks Lead Time Sudah Balik, dan Top-15 per Cabang/Perwakilan serta per Customer.
   - **Semua angka adalah link.** Nama cabang/customer memfilter ulang summary.
2. **Detail** (`/pod/detail?node=…&bucket=…&color=…&scope=…`)
   - Breadcrumb pohon (bisa naik level), chip "Rinci ke" (turun level), chip warna (toggle), cari AWB, kolom bisa diurutkan, paging 50 baris.
   - **Invarian yang dijaga test otomatis:** angka di summary = jumlah baris di detail untuk filter yang sama.
3. **Export Excel** (`/pod/export`, dari summary atau detail) berisi 5 sheet: Summary, Aging (belum balik dan lead time), Per Cabang, Per Customer, Detail.
   Sheet Detail mengikuti drill-down aktif. Tanpa drill-down, isinya semua POD Balik. Maksimal `POD_EXPORT_MAX_ROWS` baris.

### Keputusan UI/UX prototype v3 (25 Nov 2026)

- Bahasa default **English**, bisa pindah ke Bahasa Indonesia.
- Tidak ada kata/logo perusahaan di UI.
- Filter: Tanggal kirim (**preset** *atau* **rentang tanggal**), Customer, Scope, **Internal / Perwakilan**, **Branch level** (Branch, Sub Branch, Site) › **Cabang tujuan** (bertingkat).
- Semua angka cabang = **cabang tujuan** (yang mengantar & wajib mengembalikan POD). Cabang asal tampil di daftar AWB.
- Angka hari selalu bulat (tanpa desimal); persentase 1 desimal.
- Banner kondisi hanya menyebut alasan pemicu; latarnya mengikuti warna alasan utama (ungu bila penyebabnya POD kritis/ungu).
- Tabel aging: **angka** diwarnai sesuai warna umur dominan di sel itu (bukan latar).

### Master cabang dari coverage area (v5, 25 Nov 2026)

Sumber: `coverage_area_20260123.xlsx` (90.202 area). File mentah **tidak** disimpan di repo; ringkasan per cabang
dibangun dengan `python3 -I prototype/tools/build_branch_master.py <file.xlsx>` → `prototype/data/branch-master.json`.

| Kolom sumber | Dipakai sebagai |
|---|---|
| 3 huruf awal `CityCode` | Kode cabang tujuan (461 cabang domestik) |
| `Tier`: Tier 1 / Tier 2 / Remote Area (= `Tipe tujuan` KOTA UTAMA / KOTA LANJUTAN) | Branch level: **Branch (kota utama) / Sub Branch (kota lanjutan) / Site (remote area)** — 892 / 6.475 / 210 area kecamatan |
| `Office_Type` | Cabang, Satelit → **Internal**; Agen, Perwakilan → **Perwakilan** [ASUMSI untuk Satelit & Agen] |
| `RegionName` | Region rinci dari baris kecamatan (SUMATERA, KALIMANTAN, JATIMBARA, JABATENG, DKI JAKARTA, SUMAPA, …) |

Temuan kualitas data:
- **Koreksi v13 — hanya baris kecamatan yang dipakai.** 82.774 baris kelurahan (CityCode 12 karakter) selalu bernilai default `Tier 2 / Agen` dan memakai region induk. Versi sebelumnya ikut menghitungnya sehingga, misalnya, Bandung tampak punya 431 area Sub Branch/Agen (seharusnya 66 area Branch/Cabang saja) dan ~18% kiriman ke cabang utama salah tercatat sebagai Sub Branch/Perwakilan. Kelurahan hanya dipakai bila kode tidak punya baris kecamatan (BHR, ERT, MHU, PND → nama diambil dari kecamatan terbanyak; perlu nama resmi).
- **Tier melekat pada area, bukan cabang.** Pola per kode: 333 hanya Sub Branch, 42 hanya Branch, 50 Sub+Site, 23 Branch+Sub, 9 Branch+Sub+Site, 4 Branch+Site (mis. Jakarta: 43 area T1 Cabang + 3 area T2 Agen; Semarang: 17 T1 Cabang + 20 T2 Agen). Filter "Branch level" berlaku pada area tujuan kiriman.
- **Office tidak mengikuti tier:** Remote Area kebanyakan dilayani Cabang (161 vs 49 Agen); 127 area Tier 2 dilayani Cabang (mis. Bogor, Depok).
- **Region campuran** pada 10 kode (AGM, BLG, BOG, BYL, NNX, PSN, SRI, SXK, TDP, TGO) → dipakai region terbanyak.
- **Region rinci vs induk:** DKI JAKARTA mencakup Banten (Cilegon, Serang, Pandeglang, Lebak); JABASRA dan KALSTRA muncul juga sebagai region rinci untuk 9 dan 3 kode (Cirebon, Indramayu, Kuningan, Bogor, Tangerang, …; Pesawaran, BHR, MHU).
- **Dua tingkat region bersarang**: baris kecamatan memakai region rinci, baris kelurahan memakai region induk (KALSTRA = Sumatera + Kalimantan; JABASRA = Jateng + Jatimbara + DKI/Banten). Master kini menyimpan keduanya (`r` rinci, `g` induk). Perlu konfirmasi mana yang resmi untuk pelaporan.
- **Simulasi:** asal kiriman domestik = cabang yang punya area Branch dengan kantor internal (bukan lagi 6 hub); dicek otomatis: 12.000 AWB, 0 pelanggaran level/kantor/region/scope terhadap master.
- Ejaan `Office_Type` tidak seragam (Cabang/CABANG, Perwakilan/perwakilan) → dinormalisasi.
- Ada kolom tanpa judul (kolom ke-12) berisi angka pada 4 baris; diabaikan.
- 233 baris INTERNATIONAL dikecualikan.

### Mode presentasi
Tombol **Present** membuka layar penuh, menampilkan satu bagian per layar (5 slide), navigasi ← → / bar bawah, Esc keluar.
Angka tetap bisa diklik untuk membuka detail saat presentasi.

### Tanggal POS, basis umur, dan nilai Rupiah (v7)

- **Periode & date picker = tanggal POS** (transaksi dicatat). Tanggal kirim/manifest tetap disimpan untuk perjalanan dokumen.
- **"Umur dihitung dari": Delivered | POS** (default Delivered). Berlaku konsisten untuk umur, aging, warna, rata-rata umur, Lewat SLA, dan Tepat Waktu.
  Paket yang belum terkirim selalu dihitung dari POS. [ASUMSI] Saat basis POS dipilih, SLA 6/7 HK juga dihitung dari POS.
- **Setiap jumlah AWB didampingi nominal Rupiah** (ongkos kirim/freight; simulasi di prototype). Ringkas di ringkasan, lengkap di tooltip, tabel AWB, dan Excel.
- **Total kiriman** tampil paling atas: total AWB + Rp, dipecah menjadi POD Balik dan Tidak Balik (jumlah, %, Rp).

Kontrak data tambahan: `pos_date` (wajib, dasar filter periode), `freight_amount` (Rp per AWB), `chargeable_weight` (kg).

## 6. Kontrak data (integrasi produksi)

Dashboard hanya membaca satu tabel kanonik, `pod_shipments` (satu baris per AWB). Lihat
`database/migrations/*_create_pod_shipments_table.php`. Ada dua opsi pengisian:

| Opsi | Kelebihan | Kekurangan |
|---|---|---|
| **A. ETL terjadwal** (mis. tiap 15–60 menit) dari DB operasional ke `pod_shipments` | Query dashboard tidak membebani DB transaksi; skema stabil | Data tertunda sesuai jadwal; perlu job sinkronisasi |
| B. VIEW di DB operasional (read replica) dengan kolom yang sama | Real-time; tanpa job | Bergantung pada skema sistem sumber; perlu index di sumber |

**Rekomendasi: opsi A**, membaca dari read replica. Aturan pemetaan yang wajib dipenuhi sumber:

- `pod_required` = flag layanan/permintaan POD kembali per AWB.
- `pod_status` = status POD **terakhir**, dipetakan ke 8 kode di §2. NULL untuk Tidak Balik.
- `delivered_at` NULL ⇔ `pod_status = NOT_DELIVERED` (untuk POD Balik).
- `pod_returned_at` wajib terisi bila status ∈ {STPOD, PODP, ARSIP}.
- `dest_branch_*` = cabang/perwakilan yang bertanggung jawab mengembalikan POD.
- `service_scope`: **[ASUMSI]** INTRACITY bila kota asal = kota tujuan dan dilayani cabang yang sama, selain itu DOMESTIK.
- `dest_area_code` (CityCode area tujuan) → join ke master coverage untuk `dest_branch_code` (3 huruf awal), `dest_level` (Tier), `dest_office_type` (Office_Type) dan `dest_region`.
- `origin_branch_code`: cabang asal, untuk ditampilkan di detail.

Driver yang didukung oleh ekspresi umur: SQLite (dev/test), SQL Server, MySQL/MariaDB, PostgreSQL
(`app/Services/Pod/AgingSql.php`). Index yang direkomendasikan sudah ada di migrasi.

## 7. Keamanan dan operasional (wajib sebelum produksi)

- [ ] **Autentikasi + RBAC** (disetujui, setelah prototype UI/UX). Saat ini route belum dilindungi. Pasang SSO/login dan batasi user cabang hanya ke cabangnya (scope `dest_branch_code`). Isi data customer termasuk data bisnis sensitif.
- [x] Validasi whitelist semua parameter (`PodFilterRequest`). Literal SQL hanya berasal dari nilai config/enum yang tervalidasi.
- [x] Pencarian AWB meng-escape wildcard LIKE.
- [x] Rentang tanggal dibatasi (default 366 hari) dan export di-throttle (10/menit) dengan batas baris.
- [ ] Monitoring: waktu respon summary dan durasi ETL (Prometheus/Grafana yang sudah ada).
- [ ] Bila volume > ~2 juta baris per periode, pertimbangkan tabel agregat harian/materialized view untuk summary.

## 8. Daftar validasi ke user bisnis

1. Definisi resmi PODC, Redoc, STPOD, PODP; apakah STPOD/PODP dihitung "sudah balik".
2. Titik awal aging (delivered vs tanggal kirim), dan apakah memakai hari kerja.
3. Target SLA kembali per scope, mungkin juga per tier kota.
4. Definisi Intracity vs Domestik di sistem sumber.
5. Siapa saja pengguna dashboard, dan scope akses masing-masing (pusat, cabang, perwakilan, customer?).
