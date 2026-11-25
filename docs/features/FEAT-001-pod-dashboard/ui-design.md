# FEAT-001 · UI Design

Strategi semua layar: **C — code-native spec**. Prototype `prototype/pod-dashboard.html` (v8) adalah spesifikasi hidup;
komposisi memakai komponen di `docs/design.md`. Tidak ada design tool yang terhubung (katalog AnimatedWebs terblokir network policy sesi ini).

## SCR-DSH-001 · Dashboard ringkasan
**Re-verifikasi v10 (redesign visual besar, 25 Nov 2026).**
- **Kerangka:** app shell — sidebar navigasi (6 bagian, penanda bagian aktif via IntersectionObserver; disembunyikan ≤ 1180px & saat presentasi) + konten dengan band header sticky dan kartu filter.
- **Bento grid 12 kolom:**
  - *Ringkasan* — panel hero (span 8): banner status, total + bar balik/tidak balik, **3 cincin** (Returned, On time, Critical) | **Focus now** (span 4) | **5 KPI + sparkline 12 minggu POS** (span 12).
  - *Tren mingguan* — Chart.js stacked % + garis on-time.
  - *Alur status* — **diagram rincian per tahap** (default; setiap baris memecah satu tahap, lebar = % dari tahap) dengan toggle **Diagram | Tabel**.
  - *Warna & kinerja* — **donut warna umur** (span 5, segmen bisa diklik) | kinerja pengembalian (span 7).
  - *Aging* — heatmap angka berwarna (AWB | Rp, Per 5 HK | Per warna).
  - *Tindak lanjut* — peringkat dengan lencana urutan 1–3.
- **Kontrak:** `/api/pod/summary`, `/trend`, `/status-tree`, `/aging`, `/performance`, `/ranking`, `/options` (sparkline memakai `/trend`).
- **Interaksi & efek:** cincin terisi, bar/blok tumbuh, count-up, angkat + sorotan kursor, denyut ikon kritis, sorotan tertaut, klik di setiap angka/blok/segmen/cincin/minggu → modal.
- **States:** default ✓ · tanpa data ✓ · lib grafik gagal ✓ · reduced motion ✓ · HP 390px tanpa geser ✓ · loading API ✗ *gap (T9)* · error API ✗ *gap (T9)*.
- **Kesesuaian:** komponen baru (app shell, cincin, sparkline, donut, diagram alur) memakai token yang ada; tidak ada warna baru → tidak ada eskalasi ke sistem desain.

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
