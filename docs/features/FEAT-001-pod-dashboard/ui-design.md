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
