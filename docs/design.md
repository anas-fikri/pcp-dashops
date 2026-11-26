# Design System — PCP DashOps

Dokumen render-time untuk siapa pun (manusia atau agent) yang membangun UI dashboard ini.
**Nilai token bersumber dari `docs/tokens.json`**; blok CSS di bawah diturunkan dari sana.

## Provenance

```yaml
design_provenance:
  mode: extract            # varian "existing codebase" (fidelitas tertinggi)
  source: prototype/pod-dashboard.html (v8, 25 Nov 2026)
  fidelity: high
  validated_by: dataviz validate_palette.js (kontras & CVD), e2e 83 cek
  known_gaps:
    - Logo/brand resmi belum dipakai (keputusan user: polos dulu)
    - Libur nasional belum masuk kalender hari kerja
    - Katalog AnimatedWebs belum bisa dipakai (mcp.animatedwebs.com diblokir network policy sesi)
```

## 1. Aturan warna: satu warna = satu fungsi

| Fungsi | Token | Dipakai untuk | Dilarang untuk |
|---|---|---|---|
| Kontrol UI | `--accent` | Tombol utama, link, chip/segmen aktif, fokus, panel Focus, baris tertaut | Data, status |
| Status | `--success`, `--sev-oranye`, `--sev-merah` | Baik/tepat waktu; Waspada; Action needed/Lewat SLA | Kategori data |
| Umur | `--sev-hitam/oranye/merah/ungu` | Warna umur relatif SLA customer | Hal lain selain umur (mis. "tertahan" **tidak** ungu) |
| Data | `--data-req/ret/pending/none` | POD balik / Sudah balik / Belum balik / Tidak balik | UI, status |
| Porsi tanpa kategori | `--muted` | Bar "% induk", meter di kartu Focus | — |

- Warna yang mirip (mis. `--data-req` vs `--data-ret`) **tidak boleh berdampingan dalam satu grafik**.
- Pasangan yang berdampingan sudah lulus `validate_palette.js`: tren (ret/pending/good) ΔE ≥ 16, bar total (req/none) ΔE ≥ 34.
- Warna dengan kontras < 3:1 terhadap latar (`--data-pending`, `--data-none`) **wajib** disertai label/legenda/tooltip.
- Teks yang menyebut warna memakai pil berlatar warna itu (`.mention.m-ungu` dst.).

### Arah visual: energik tapi clean
- **Energi** dari band header gradasi navy (`--hero-a/b/c`) dengan kilau bergerak 14 dtk, angka KPI 34px rapat (-0.035em), penanda aksen 4px di setiap judul bagian, gerak mikro (masuk, tumbuh, count-up, angkat).
- **Clean** lewat latar terang/netral, satu aksen, banyak ruang putih, border tipis, tanpa ornamen atau ilustrasi.
- Energi **tidak** boleh datang dari warna data/status baru (aturan §1 tetap berlaku).

## 2. Token CSS (turunan tokens.json)

```css
:root {
  --bg:#f7f6f4; --card:#fff; --soft:#f1efec; --hi:#eef2f8;
  --ink:#26282d; --muted:#5d6169; --line:#e4e1dc;
  --accent:#2f4a6d; --accent-strong:#233a57; --on-accent:#fff; --accent-soft:#dfe7f2;
  --success:#1a7a43; --success-soft:#e3f3ea;
  --sev-hitam:#3d4049; --sev-oranye:#e8930c; --sev-oranye-ink:#9a5800; --sev-merah:#b3202a; --sev-ungu:#7b2fbe;
  --data-req:#475d7a; --data-ret:#2a78c8; --data-pending:#98a3b4; --data-none:#d5d9e0;
  --font:"Plus Jakarta Sans",system-ui,-apple-system,"Segoe UI",sans-serif;
  --r-sm:8px; --r-lg:20px; --r-pill:9999px;
  --ease:cubic-bezier(.2,.8,.2,1); --dur-fast:150ms; --dur:250ms; --dur-slow:650ms;
  --hero-a:#0f1d33; --hero-b:#233a57; --hero-c:#355a8a; --on-hero:#fff; --glow:rgba(53,90,138,.10);
}
/* Mode gelap: nilai di tokens.json $extensions.mode.dark, diterapkan via
   @media (prefers-color-scheme: dark) :root:not([data-theme="light"]) dan :root[data-theme="dark"] */
```

## 3. Komponen

| Komponen | Kelas | Variants / states | Aturan |
|---|---|---|---|
| Band header | `.top` | default, sticky (desktop), presentasi | Gradasi `--hero-*` + kilau; tombol putih/outline putih; teks putih ≥ 4.5:1 |
| App shell | `.shell > .side + .wrap` | bagian aktif, ≤1180px sidebar disembunyikan | Navigasi ke 6 bagian; aksen hanya untuk penanda aktif |
| Cincin indikator | `.ring` | hover (tebal), animasi isi | Warna = fungsi (Returned `--data-ret`, On time `--success`, Critical `--sev-ungu`); persentase + jumlah·Rp |
| Sparkline | `.spark` | ≥2 titik; titik on-time disembunyikan bila < 10 | Area 14% + garis 2px + titik akhir; tooltip "awal → akhir" |
| Donut warna umur | `.donut` | hover segmen menebal | Jarak 3px antar segmen; tengah = jumlah + Rp belum balik |
| Diagram alur | `.flow-row > .flow-par + .flow-bar > .blk` | blok sempit (<14%) ringkas | Setiap baris = satu tahap; lebar blok = % dari tahap; keluarga warna data |
| Strip total | `.totals` | default, hover legend, kosong (0 AWB) | Total AWB + Rp → bar 2 segmen (POD balik / tidak balik), tiap segmen bisa diklik |
| Banner status | `.status.lvl-good/warn/crit` | 3 level | Latar netral; garis kiri, ikon, judul = warna level; alasan = pil masing-masing; crit berdenyut |
| Kotak KPI | `.kpi` | default, hover (angkat + sorotan kursor), focus-visible, active | Titik warna = kategori data/status; angka count-up; selalu ada baris Rp |
| Kartu Focus | `.fcard` | default, hover, kosong | Nama, jumlah·%·kritis, Rp tertahan, meter netral |
| Angka AWB + Rp | `.nv` | — | **Setiap jumlah AWB wajib disertai nominal Rp** (ringkas; lengkap di tooltip) |
| Batang warna umur | `.sevbar` (`.lg`) | hover segmen membesar | Segmen bisa diklik → modal tersaring warna |
| Sel aging | `.hm td.c` | nol (redup), hover (angka membesar) | Angka berwarna sesuai warna umur dominan; latar polos |
| Tren mingguan | `#trend` (Chart.js) | loading lib gagal, kosong, <10 POD kembali (titik disembunyikan) | Satu sumbu 0–100%; klik minggu → modal |
| Hint | `.info` | hover, fokus, ketuk (pinned) | Area klik ≥ 33px; teks dari i18n |
| Chip / segmen | `.chip`, `.seg` | on/off, active (scale .97) | Pilihan aktif = accent |
| Modal | `.modal > .dialog` | list, detail AWB, kosong | Muncul dengan pop + blur latar; Esc menutup; fokus terkunci |
| Date range picker | `.dp` | pilih awal, pilih akhir, disabled (masa depan) | 2 bulan di desktop, 1 di HP (posisi fixed) |
| Mode presentasi | `body.present` | 6 slide | Satu bagian per layar, ← → / Esc |

## 4. Motion

| Efek | Token | Kapan |
|---|---|---|
| Kilau band header | 14s alternate | Selalu (mati saat reduced motion) |
| Masuk kartu (naik 12px) | `--dur-slow`, `--ease`, stagger 60ms | Load & re-render |
| Bar tumbuh dari kiri | `--dur-slow` | Meter, sevbar, bar total |
| Count-up angka | 650ms ease-out cubic | KPI & total saat filter berubah |
| Angkat + sorotan kursor | `--dur` | Hover kotak KPI & kartu Focus |
| Denyut ikon | 2s loop | Banner Action needed saja |
| Pop modal + blur | `--dur` | Buka modal |
| Sorotan tertaut | `--dur-fast` | Hover baris status → baris sama di Aging |

**Wajib:** `prefers-reduced-motion: reduce` mematikan semua animasi & transisi; konten selalu lengkap tanpa animasi (tidak ada elemen yang mulai dari opacity 0).

## 5. Aksesibilitas
- Teks ≥ 4.5:1; mark grafik ≥ 3:1 atau berlabel. Identitas tidak pernah hanya lewat warna (selalu ada label/legenda/tooltip).
- Semua angka yang bisa diklik adalah `<button>`; Enter membuka hint; Tab terkunci di modal; Esc menutup.
- Bahasa default English, toggle Indonesia untuk semua teks termasuk hint & Excel.

## 6. Do / Don't
- ✅ Tambah angka AWB → sertakan Rp (`nv()`).
- ✅ Warna baru → tambahkan dulu ke `tokens.json` dengan fungsi tunggal, validasi dengan `validate_palette.js`.
- ❌ Jangan memakai ungu/merah untuk hal yang bukan umur/batas.
- ❌ Jangan memakai accent untuk mark data.
- ❌ Jangan grafik dua sumbu.

## 7. Agent quick-reference
Token: `docs/tokens.json` · Prototype referensi: `prototype/pod-dashboard.html` · Layar: `docs/ux-foundations.md` (SCR-DSH-*) · Spesifikasi layar: `docs/features/FEAT-001-pod-dashboard/ui-design.md` · Kontrak API: `.../technical-design.md`.


## v11 — aturan anti "AI look" & palet bersih (25 Nov 2026)
- **Hindari:** gradasi dekoratif/kilau, glow, bayangan di semua kartu, garis aksen kiri di setiap blok, tanda di setiap judul, grid kartu identik, cincin/gauge untuk KPI vs target.
- **Pakai:** header datar `--hero-b`; kartu datar dengan garis `--line`; strip KPI bergaris rambut 1px; **bullet graph** (`.bullet`, pita `--band-1..3`, garis target `.bl-tgt`) untuk KPI vs target; delta `.delta.up-good/down-bad/...`.
- **Palet bersih (light / dark):** data-req `#0e7490`/`#2dd4bf`, data-ret `#2563eb`/`#60a5fa`, pending `#94a3b8`/`#64748b`, none `#cbd5e1`/`#334155`; hitam `#475569`/`#cbd5e1`, oranye `#f59e0b`/`#fbbf24` (teks `#b45309`), merah `#dc2626`/`#f87171`, ungu `#7c3aed`/`#a78bfa`; success `#15803d`/`#4ade80`; aksen `#1e3a8a`/`#93c5fd`. Nilai otoritatif ada di tokens.json.
- **Validasi:** `validate_palette.js` lulus CVD & jarak normal untuk set tren, umur, KPI, split; peringatan kontras (pending, oranye, none < 3:1) diatasi label angka langsung. Hijau↔merah (deutan ΔE 7.9) tidak pernah bersebelahan tanpa label.
- **Export per kelompok:** setiap kartu punya `.xbtn[data-xport]`; isi = angka persis yang tampil.

## v12 — komponen tambahan
| Komponen | Kelas | Aturan |
|---|---|---|
| Perbandingan scope | `.scp-split`, `.scp-col` (`.off` saat tersaring) | Porsi memakai netral (`--ink` / `--band-1`) + label — scope bukan warna fungsi baru |
| Baris kinerja | `.pv-row` | Bullet on-time (target 80%) + Returned · Avg HK · Lewat SLA, semuanya bisa diklik |
| Grafik aging | `.ag-chart > .ag-col` | Batang kiri = belum balik dipecah warna umur; batang kanan = sudah balik `--data-ret`; label jumlah + Rp di atas batang |
| Back to top | `.totop` | Muncul setelah gulir 600px; disembunyikan saat presentasi; hormati reduced motion |

## v14 — pohon status & navigasi
| Komponen | Kelas | Aturan |
|---|---|---|
| Pohon status | `.ptree > li > .pnode` (+ `ul` bersarang) | Struktur = sketsa; garis penghubung `--band-1` 2px; aksen kiri kartu = keluarga warna data (`--data-req/none/pending/ret`); batang warna umur hanya di cabang Belum Balik; ≤860px menjadi daftar bertingkat |
| Lensa scope | `#tl-seg [data-tl]` | All / Intracity / Domestic; angka & modal ikut lensa; nonaktif bila filter Scope global aktif |
| Legenda | `.legend-strip` | Status (4 warna data) + warna umur dengan rentang hari SLA 6 |
| Navigasi bagian | `.secnav a[data-nav]` | Di dalam header; aktif = pil putih; tidak tampil saat presentasi |

## v15 — aturan keterbacaan
- **Setiap persentase menyebut pembandingnya** di teks konteks KPI ("X dari Y …").
- **Satu angka = satu tempat utama.** Angka ringkas di KPI; rincian di pohon; jangan ulang di blok lain pada layar yang sama.
- **Satu istilah per konsep:** Belum balik (bukan outstanding/tertahan), Kritis = ungu, Lewat SLA = delivered belum balik > SLA.
- **Aging hanya untuk POD belum balik**; lama kembali POD sudah balik adalah ukuran kinerja.
- Komponen: `.now .kpis > .kpi[data-k]` (label · angka + Rp · visual (`.kbar` / `.sevbar` / `.bl-track` dengan `.bl-tgt`) · `.ctx`); `.atabs` + `.apanel` (judul panel disembunyikan, tab yang menamai).

## v16 — aturan "ringkasan saja"
- Halaman dashboard **tidak memakai pengalih tampilan** (switch/tab/toggle). Kontrol yang boleh: filter, bahasa, presentasi, export.
- **Setiap jumlah AWB tampil bersama Rp** di tempat yang sama (komponen `.nv` atau label count + Rp).
- **Detail = modal.** Semua angka/kotak/baris ringkasan adalah tombol yang membuka daftar AWB dengan jumlah yang sama.
- Komponen baru: `.ssum` (Intracity vs Domestik di bawah pohon), `.rank2 > .rk-list > .rk-row` (10 teratas cabang tujuan & customer).

## v17 — tren di detail
| Komponen | Kelas | Aturan |
|---|---|---|
| Tren mingguan tepat waktu | `.wtrend > .wt-scroll > .wt-grid > .wt-col` | Hanya di modal KPI On time; batang `--success` bila ≥ target, `--sev-oranye` bila di bawah (status), `--data-none` bila < 10 POD; garis target `--ink` 2px; setiap kolom = tombol filter minggu |

## v18 — komponen yang dipensiunkan (kode sudah dihapus dari prototype)
Tidak dipakai lagi dan **jangan diimplementasikan** di Laravel: app shell/sidebar (`.shell`, `.side`), cincin (`.ring`), bullet graph mandiri (`.bullet`), total terpisah (`.totals`, `.split-*`), panel Focus (`.focus`, `.fcard`), strip sejak kunjungan terakhir (`.since`), sparkline (`.spark`), donut warna umur (`.donut`), diagram alur baris (`.flow-row`, `.blk`), heatmap aging (`.hm`), tabel pohon, tab peringkat/analisis (`.tabs`, `.atabs`), kartu Intracity vs Domestik besar (`.scp-*`), baris kinerja (`.pv-*`), grafik tren Chart.js. Pengalih tampilan (`.seg` di luar filter/header) tidak dipakai di halaman ringkasan.

## v19 — papan status & filter ringkas
| Komponen | Kelas | Aturan |
|---|---|---|
| Papan status | `.sb-split` (total + `.sb-bar` + `.sb-leg`), `.sb-step` (bar tipis), `.sb-cards > .sb-card > .sb-head + .sb-row(.child)` | Bentuk dataviz: segmented bar untuk 2 bagian, meter list untuk banyak bagian. Setiap baris = tombol ke daftar AWB; batang = porsi dari baris induk; batang Belum balik berwarna umur (segmen hanya tooltip, klik = seluruh baris) |
| Intracity vs Domestik | `.ss-tab > .ss-c[data-sc][data-id]` | Satu tabel dua kolom; kolom yang tersaring filter Scope tampil "–" |
| Filter | `.fdet > .fgrid` | 2 baris di desktop; dilipat di ≤860px dengan ringkasan; `#active-filters` disembunyikan bila kosong |
| Teks merah | `--sev-merah-ink` | Angka/teks merah memakai token ini (kontras ≥ 4,5:1); blok & garis tetap `--sev-merah` |
Pohon kiri→kanan (`.ptree`, `.pnode`) **dipensiunkan**.

## v20 — bahasa visual tunggal & peta (26 Nov 2026)
**Aturan inti**
- Panjang batang = jumlah AWB. Warna hanya punya satu arti:

| Warna (token) | Arti | Dipakai di |
|---|---|---|
| `--data-none` abu | Tidak balik | batang semua kiriman, legenda |
| `--data-ret` biru | Sudah balik | batang, status dokumen, rute |
| `--sev-hitam/oranye/merah/ungu` | Umur POD belum balik (relatif SLA) | batang, pie peta, kolom umur, tindak lanjut |
| `--success` hijau | Target tercapai | angka & batang Tepat waktu bila ≥ 80% |
| `--ink` (aksen) | Elemen interaktif | tombol utama, pilihan aktif, fokus |

- Biru **tidak** dipakai untuk UI. Merah/oranye **tidak** dipakai untuk status target (di bawah target = tinta netral + teks).
- Tanpa ornamen: tanpa bayangan, gradasi (kecuali contoh warna legenda), animasi, count-up, ikon (i); istilah = garis titik-titik + tooltip.
- Teks angka memakai tinta; teks merah memakai `--sev-merah-ink`.

**Komponen**
| Komponen | Kelas | Catatan |
|---|---|---|
| Peta sebaran | `#map svg.map > path.pv + g.mb + text.ml` | Proyeksi ekuirektangular 1000×376; data `GEO` dari `tools/build_geo.py`; lingkaran = pie umur; `g.mb` role=button + tabindex |
| Kondisi | `.verdict` + `.kpis > .kpi` (`.k-l`, `.k-v`, `.trk`, `.k-c`) | 4 angka, tanpa kartu |
| Batang semua kiriman | `.fb` (`.fb-all`, `.fb-brace`, `.fb-bar`, `.fb-legs`) | kurung = POD balik |
| Batang status | `.fd > .fd-col > .hb(.child)` | skala per kolom |
| Rute | `.scs > .sc` | 100% POD balik: biru + umur |
| Tindak lanjut | `.rank2 .rk-row` | panjang relatif ke teratas |
| Legenda | `#legend` | satu untuk seluruh halaman |
Dipensiunkan: papan status v19 (`.sb-*`, `.ss-*`), band header biru, navigasi bagian, panel Focus, filter Scope.

## v21 — penegasan aturan warna & detail (26 Nov 2026)
- **Warna umur (hitam/oranye/merah/ungu) hanya untuk POD belum balik.** POD sudah balik dinilai *tepat waktu* vs *terlambat*, tidak pernah diberi warna umur (layar maupun kolom "Warna umur" di Excel).
- **Target:** tercapai = `--success` (isi hijau); di bawah target / terlambat = **berongga** (`.bg-late`: latar `--card`, garis 1,5 px `--ink`). Abu tua tidak dipakai karena `--muted` (#475569) = `--sev-hitam`; abu muda milik "tidak balik". Sampel terlalu kecil = `--line`.
- **Header lengket** (≥ 900 px) wajib berlatar `--bg`.
- **Tooltip** ditutup saat klik, gulir, dan masuk presentasi; `.term` bisa diketuk (layar sentuh).

| Komponen detail | Kelas | Catatan |
|---|---|---|
| Baris saringan + rincian | `.mbar-row` | chip saringan aktif (hapus ×), "Rincian" anak titik, "Lihat di dashboard" di kanan |
| Angka detail | `.mstats > .mstat[data-k]` | 4 angka datar dipisah garis: share, val, age, scope |
| Batang sebaran | `.mdist > .mbar + .mleg` | segmen = AWB; legenda = tombol saring (`data-msev` / `data-mset`) |
| Penyumbang | `details.mwhy` | terbuka di desktop, terlipat di HP |
| Tabel | `table.mtable` | 8 kolom, header `.th-b` = tombol urut; HP: satu blok per AWB, kolom tanggal `.c-dt` disembunyikan |
Dipensiunkan: `.sevbar`, `info()` di detail (ikon (i)), kolom Asal/Scope/SLA/Ref terpisah.

**Tooltip peta (kartu visual).** Lingkaran, wilayah provinsi, dan baris "Provinsi dengan belum balik terbanyak" memakai `data-tipp` → `.tip.rich` (latar `--card`, bukan tooltip gelap, agar warna umur tampil sama seperti di halaman): nama + peringkat `#n / total`, angka AWB besar + Rp, batang warna umur yang panjangnya relatif ke provinsi terbanyak (sisa = trek `--soft`), jumlah per 4 warna (posisi tetap; 0 dibuat pudar), 3 cabang tujuan teratas dengan batang mini. Kartu provinsi didahulukan atas tooltip segmen di dalamnya.
