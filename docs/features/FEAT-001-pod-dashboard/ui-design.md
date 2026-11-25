# FEAT-001 · UI Design

Strategi semua layar: **C — code-native spec**. Prototype `prototype/pod-dashboard.html` (v11) adalah spesifikasi hidup;
komposisi memakai komponen di `docs/design.md`. Tidak ada design tool yang terhubung (katalog AnimatedWebs terblokir network policy sesi ini).

## SCR-DSH-001 · Dashboard ringkasan
**Re-verifikasi v11 (anti "AI look", warna bersih, 25 Nov 2026).**
- **Kerangka:** tanpa sidebar — band header datar (biru tegas, tanpa gradasi/kilau) + kartu filter + bento grid 12 kolom.
- **Ringkasan:** banner status · strip *Since your last visit* · total + bar balik/tidak balik · **3 bullet graph** (Returned; On-time dengan target 80% + pita 0–60/60–80/80–100 + delta vs periode sebelumnya; Critical dengan batas 15%) menggantikan 3 cincin | Focus now | strip 5 KPI (garis rambut 1px, tanpa bayangan) + sparkline.
- **Export per kelompok:** tombol Excel di setiap kepala kartu (now, trend, flow, sev, perf, aging, rank) + export total + export modal.
- **Keterlibatan:** sejak kunjungan terakhir (per kombinasi filter), *Save as my view*, delta periode sebelumnya, Focus now.
- **Kontrak:** `/api/pod/summary` (+ `prev` untuk delta on-time), `/trend`, `/status-tree`, `/aging`, `/performance`, `/ranking`, `/options`, `/export?group=`.
- **States:** default ✓ · tanpa data ✓ · lib grafik gagal ✓ · reduced motion ✓ · HP 390px ✓ · kunjungan pertama ✓ · loading/error API ✗ *gap (T9)*.
- **Kesesuaian:** palet diganti ke nilai yang lebih jernih (lihat design.md §1), peran warna tidak berubah; tokens.json disinkronkan (52 nilai cocok).

**Tambahan v12 (25 Nov 2026).**
- **Intracity vs Domestic** (bagian baru `#s-scope`, slide 4/7): bar porsi kiriman, dua kolom berdampingan (total · POD harus balik · belum balik, bullet Returned / On-time / Critical, bar warna umur, rata-rata HK, lewat SLA) + kalimat kesimpulan. "Show only this" → filter Scope; kolom yang tersaring tampil redup dengan "Compare both".
- **Data simulasi:** intracity kini terjadi di setiap cabang/sub cabang (asal = tujuan), bukan hanya 6 hub → filter cabang/region + Intracity tidak lagi kosong.
- **Lebih visual:** Kinerja pengembalian default **Grafik** (baris bullet on-time + target 80%) | Tabel; Aging default **Grafik kolom** (belum balik per warna umur vs sudah balik per rentang HK) | Tabel.
- **Back to top** mengambang setelah gulir 600px; tombol simpan → **"Save my filters / Simpan filter saya"**.
- Export per kelompok: 8 (tambah `scope`). E2E 101/101.

**v14 — kembali ke sketsa sebagai tulang punggung (25 Nov 2026).**
- **Prinsip:** urutan halaman mengikuti sketsa + mantra Shneiderman (ringkasan → pohon → zoom/filter → detail on demand):
  1 Kondisi · 2 **Pohon Status** · 3 Aging per 5 HK · 4 Tindak lanjut · 5 Intracity vs Domestik · 6 Tren · 7 Warna & kinerja.
- **Pohon Status (`#s-tree`, default "Tree")**: pohon kiri→kanan persis struktur sketsa (Semua → Tidak Balik / POD Balik → Belum / Sudah Balik → Not Yet Delivered / Delivered → Masih di pelanggan, PODC, Redoc, Upload; STPOD, PODP, Arsip). Kartu = jumlah + Rp + % dari induk + batang porsi; cabang "Belum Balik" juga menampilkan batang warna umur. Lensa **All | Intracity | Domestic** menggantikan garis silang Intracity/Domestik di sketsa (terkunci bila filter Scope aktif). Legenda status + warna umur (rentang hari SLA 6, +1 untuk SLA 7). Toggle Tree | Table.
- **Navigasi bagian** di header (7 tautan, menandai bagian aktif) sebagai pengganti sidebar.
- HP: pohon menjadi daftar bertingkat dengan garis kiri. E2E 106/106.

**v15 — re-verifikasi keterbacaan (25 Nov 2026).**
Temuan tinjauan halaman (sebelum perubahan):
1. Layar pertama memuat ±40 angka (banner, total, 3 bullet, Focus, 5 KPI); angka yang sama tampil 2–3 kali.
2. Persentase tanpa pembanding ("79,8%" dari total, "84,9%" dari POD balik, "28,4%" dari delivered, "57,0%" dari sudah balik).
3. Sinonim untuk satu konsep: not returned / outstanding / on hold; past SLA / late / critical.
4. Aging memakai 2 batang per rentang (belum balik = umur, sudah balik = lama kembali) — dua ukuran di satu sumbu; sketsa hanya meng-aging Belum Balik.
5. 7 bagian tingkat atas (panduan: 4–6).

Keputusan:
- **Kondisi:** banner + **5 KPI** (Total kiriman · Sudah balik · Belum balik · Tepat waktu · Kritis). Bullet target dilebur ke KPI (garis 80% & batas 15%); setiap KPI menulis pembandingnya ("4.088 dari 4.816 POD balik"). Total & bullet terpisah dihapus; sparkline dipindah ke tab Tren.
- **Aging:** hanya POD belum balik (satu batang per rentang, dipecah warna umur); tabel tanpa bagian "sudah balik"; lama kembali ada di Analisis › Kinerja.
- **Analisis (`#s-ana`) bertab:** Intracity vs Domestik · Kinerja Pengembalian · Warna Umur · Tren Mingguan. Halaman 5 bagian: Kondisi · Pohon · Aging · Tindak lanjut · Analisis (presentasi 5 slide).
- **Istilah tunggal:** "Belum balik / Not returned" menggantikan outstanding/on hold/tertahan.
- Hasil: tinggi halaman desktop 5.503 → ±4.300 px; E2E 106/106 (cek baru: KPI menyebut pembanding, target 80% & batas 15% di KPI, tanpa bullet/total ganda).

**v16 — dashboard = ringkasan saja; detail hanya lewat klik (25 Nov 2026).**
Masukan pengguna: masih terlalu rumit, terlalu banyak yang harus diklik; AWB dan Rp harus tampil bersamaan tanpa switch.
- **Prinsip:** halaman hanya berisi ringkasan dengan urutan sketsa; **semua detail ada di modal** yang terbuka saat ringkasan diklik.
- **Isi halaman (4 bagian):** Kondisi (banner + 5 KPI) · Pohon Status (14 kotak + ringkasan Intracity vs Domestik) · Aging per 5 HK (POD belum balik) · Siapa ditindaklanjuti (10 cabang tujuan + 10 customer).
- **Dihapus dari halaman:** semua pengalih (AWB|Rp, Grafik|Tabel, Pohon|Tabel, lensa scope, Bucket|Warna, urutan, tab peringkat, tab analisis, Tampilkan semua), panel Focus, strip "sejak kunjungan terakhir", tab Analisis (tren, donut warna, kinerja). Chart.js tidak lagi dimuat.
- **AWB + Rp selalu bersamaan** di KPI, kotak pohon, Intracity/Domestik, label aging, dan baris tindak lanjut.
- Export per kelompok: now · flow · aging · rank. Presentasi 4 slide. Tinggi desktop ±3.600 px.
- E2E ditulis ulang (59/59): tidak ada pengalih di ringkasan; setiap angka ringkasan memuat Rp; pohon 14 kotak AWB & Rp = modal; induk = jumlah anak; KPI/Intracity-Domestik/aging/tindak lanjut = modal & konsisten dengan pohon; modal, filter, export, ID, presentasi, keyboard, HP.

## SCR-DSH-002 · Daftar AWB (modal)
- **Komposisi:** breadcrumb (jumlah + Rp per level), chip saringan aktif, chip rinci, "Lihat posisinya di dashboard", 5 statistik, batang & chip warna, penyumbang terbesar, cari, export, tabel 13 kolom (urut), paging.
- **Kontrak:** `/api/pod/awb`. **States:** default ✓, cari kosong ✓, dokumen PODP/STPOD ✓, paging ✓, loading/error ✗ *gap (T9)*.

## SCR-DSH-003 · Detail AWB
- Ringkasan (status, umur + warna + kalimat SLA sesuai basis, customer, asal, cabang tujuan, dilayani oleh, scope, nilai & berat) · perjalanan dokumen (POS → kirim → delivered → status/STPOD/PODP/arsip) · data terkait (jumlah + Rp).
- **Kontrak:** `/api/pod/awb/{awb}`. **States:** belum terkirim ✓, delivered belum balik ✓, sudah balik ✓, tidak balik ✓.

## SCR-DSH-004 · Mode presentasi
6 slide (Kondisi, Tren, Alur, Warna+Kinerja, Aging, Peringkat); ← → / bar bawah / Esc; fullscreen ditolak → tetap jalan ✓; modal di atas slide ✓.

## SCR-DSH-005 · Export Excel · SCR-DSH-006 · Date range picker
Lihat prototype; states sukses/dibatalkan/lib gagal/tidak tersedia ✓; picker: pilih awal/akhir, masa depan nonaktif, 1 bulan di HP ✓.

## Kesesuaian sistem desain
Semua warna mengikuti design.md §1 (satu warna = satu fungsi); pasangan berdampingan divalidasi `validate_palette.js`. Tidak ada eskalasi ke sistem desain; penambahan palet **data** dicatat di tokens.json v8.
