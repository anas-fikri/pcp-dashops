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
