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

**v17 — tren mingguan di detail KPI "On time" (25 Nov 2026).**
- Tren tidak tampil di halaman ringkasan; muncul hanya saat KPI **On time** diklik (modal `{node:'sudah', ot:true}`), di bawah statistik modal.
- Batang per minggu POS = % POD sudah balik yang kembali sesuai SLA; garis = target 80%; hijau = tercapai, oranye = di bawah target (warna status, sama dengan banner), abu = < 10 POD kembali (terlalu sedikit).
- Di bawah batang: tepat waktu / sudah balik + nilai Rp. Klik minggu → daftar AWB minggu itu (chip "POS week of …", bisa dilepas). Di HP grafik bisa digeser dan langsung menampilkan minggu terbaru.
- E2E 63/63: jumlah tepat waktu semua minggu = KPI; klik minggu = jumlah modal; tren tidak ada di halaman maupun di detail lain.

**v18 — pembersihan kode (25 Nov 2026).** Tanpa perubahan tampilan atau perilaku. Dihapus: 22 fungsi/konstanta JS yang tidak terpanggil (ESLint `no-unused-vars` + espree, berulang sampai stabil), 10 event handler untuk kontrol yang sudah tidak ada, cabang export trend/scope/sev/perf, state lama (`fv, tl, at, pv, av, am, metric, rsort, rank, rankAll`), 185 selector CSS tanpa pemakai, 125 kunci teks EN/ID tanpa rujukan. Export "Tindak lanjut" kini berisi dua sheet (cabang tujuan & customer) sesuai yang tampil. File 255.737 → 203.170 byte (2.329 → 1.819 baris). Verifikasi: ESLint bersih; e2e 63/63; screenshot sebelum/sesudah identik piksel per piksel (halaman, modal, detail AWB, kalender, tooltip, presentasi, bahasa Indonesia; terang & gelap); ukuran 61 elemen identik di 390px & 1440px.

**v19 — review keterbacaan < 1 menit + pengganti pohon (25 Nov 2026).** Skill: design-critique, dataviz (pemilihan bentuk), accessibility-review (axe-core 4.10, WCAG 2.1 AA).
- **Temuan:** (1) filter memakan ±40% layar laptop 1366×768 sehingga KPI terpotong; di HP angka pertama di 1.469px. (2) Pohon kiri→kanan: 14 kotak + garis penghubung, mata melompat ke banyak arah, ±1.200px. (3) Tindak lanjut 3 tingkat per baris. (4) Kontras teks merah 4,26:1 / 3,95:1 < 4,5:1; aging tidak bisa dibuka dengan keyboard.
- **Papan status (pengganti pohon)** — bentuk dari dataviz: *segmented bar* untuk pembagian dua bagian, *meter list* (nama · batang sejajar · AWB + Rp · %) untuk banyak bagian. Urutan tetap sketsa, dibaca atas→bawah: Semua kiriman → POD balik | Tidak balik → (bar) Belum balik | Sudah balik → dua kartu: Belum balik (Belum terkirim, Delivered › Masih di pelanggan, PODC, Redoc, Upload; batang berwarna umur) dan Sudah balik (STPOD, PODP, Arsip). Ringkasan Intracity vs Domestik jadi satu tabel dua kolom.
- **Filter ringkas:** 2 baris tanpa judul grup/nomor langkah; chip filter hanya tampil bila ada; di HP dilipat ("Filter · Last 90 days · All").
- **Tindak lanjut:** satu baris per item (no · nama · warna umur + jumlah kritis · AWB + Rp).
- **Aksesibilitas:** token teks merah `--sev-merah-ink` (#b91c1c terang); label jumlah aging = tombol (keyboard). axe-core: 0 pelanggaran di 6 kondisi.
- **Hasil:** KPI mulai 479 px (sebelumnya 619); banner + 5 KPI muat di layar laptop 1366×768; HP: KPI di 751 px (sebelumnya 1.469); tinggi desktop 3.608 → 3.060 px. E2E 69/69.

**v20 — analisis ulang dari sketsa + redesign visual (26 Nov 2026).**

*Analisis kebutuhan (sketsa):* catatan menggambarkan satu laporan "Output: excel performance POD" — baris = pohon status (POD → Tidak balik / Balik → Intracity/Domestik → Belum balik [Not yet delivered, Delivered → masih di pelanggan, PODC, Redoc, Upload] / Sudah balik [STPOD, PODP, Arsip]), "Aging per 5 hari × 6", warna tanggal 1–3 hitam / 4–5 oranye / 6–8 merah / ≥10 ungu, filter customer / PCP-perwakilan / tier kota. Target pengguna: simpel, < 1 menit, warna = fungsi, tidak terlihat buatan AI, halaman hanya ringkasan tetapi setiap angka membawa detail & bisa diekspor; utamakan visualisasi; tambah peta sebaran di paling atas.

*Keputusan:*
- **Satu bahasa visual** untuk seluruh halaman: panjang batang = jumlah AWB; warna = fungsi (abu = tidak balik, biru = sudah balik, hitam/oranye/merah/ungu = umur POD belum balik, hijau = target tercapai). UI interaktif memakai tinta netral (biru tidak dipakai untuk tombol). Satu legenda untuk seluruh halaman.
- **Urutan:** 1 Sebaran (peta) · 2 Kondisi (1 kalimat + 4 angka) · 3 Alur POD (satu batang semua kiriman dengan kurung "POD balik", rincian status sebagai batang horizontal, Intracity/Domestik sebagai batang 100%) · 4 Umur per 5 HK (kolom bertumpuk warna umur) · 5 Tindak lanjut (dua grafik batang 10 teratas).
- **Peta sebaran:** batas 34 provinsi (BAKOSURTANAL) netral; satu lingkaran per provinsi cabang tujuan (ukuran = AWB belum balik, isi = pie warna umur), angka AWB + Rp di samping dengan penempatan anti-tumpang-tindih; daftar 8 provinsi teratas di kanan; cabang tanpa lokasi ditampilkan sebagai "Lokasi belum terpetakan". Total lingkaran + belum terpetakan = Belum balik.
- **Anti "AI look":** tanpa band warna di header, tanpa kartu melayang/bayangan/gradasi/animasi/count-up/sorotan kursor, tanpa ikon (i) (istilah bergaris titik-titik + tooltip), judul kalimat biasa, huruf sistem, angka tabular.
- **Export:** setiap kelompok (peta, kondisi, alur, umur, tindak lanjut) berisi ringkasannya + sheet "Detail AWB"; "Unduh Excel" diawali sheet "Performance POD" = laporan sketsa (14 status × AWB, Rp, %, Intracity, Domestik, 7 rentang umur, 4 warna).
- **Filter:** satu baris (Scope dihapus karena Intracity/Domestik sudah selalu tampil); urutan sketsa: customer → internal/perwakilan → level → region → cabang tujuan.
- **Mode gelap:** "tidak balik" #4b5563 (dari #334155, kontras 1,78 → 2,44), palet umur gelap tervalidasi (validate_palette: CVD & normal-vision lulus).

*Verifikasi:* E2E v20 57/57 (termasuk: tidak ada warna di luar token fungsi, tanpa ornamen, peta = modal & = belum balik, label peta tidak tumpang-tindih, setiap export berisi "Detail AWB", sheet pertama = laporan sketsa dengan total = layar). axe-core WCAG 2.1 AA: 0 pelanggaran (6 kondisi). Tinggi desktop 2.529 px (peta + daftar muat di layar 1366×768).

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
