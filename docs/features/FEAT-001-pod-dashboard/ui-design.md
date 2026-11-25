# FEAT-001 · UI Design

Strategi semua layar: **C — code-native spec**. Prototype `prototype/pod-dashboard.html` (v8) adalah spesifikasi hidup;
komposisi memakai komponen di `docs/design.md`. Tidak ada design tool yang terhubung (katalog AnimatedWebs terblokir network policy sesi ini).

## SCR-DSH-001 · Dashboard ringkasan
- **Komposisi:** header sticky (judul, ringkasan filter, EN/ID, Present, Export) → kartu filter (POS date preset/rentang, Age from, Customer, Scope, blok bertingkat Region › Internal/Perwakilan › Level › Cabang tujuan) → *Kondisi POD Saat Ini* (strip total, banner status, 5 KPI, Focus now) → Tren mingguan → Alur status → Warna umur + Kinerja → Aging (AWB|Rp, Per 5 HK|Per warna) → Siapa ditindaklanjuti (tab, AWB|Rp) → Istilah & aturan.
- **Kontrak:** `/api/pod/summary`, `/trend`, `/status-tree`, `/aging`, `/performance`, `/ranking`, `/options`.
- **Interaksi & efek:** count-up angka, kartu masuk berurutan, bar tumbuh, angkat + sorotan kursor pada KPI/Focus, denyut ikon Action needed, sorotan tertaut baris status ↔ aging, tooltip grafik dengan jumlah + Rp, klik minggu/angka/segmen → modal.
- **States:** default ✓ · filter tanpa data ✓ (pesan "No outstanding POD…", tren "No POD to return") · lib grafik gagal ✓ (pesan, angka lain tetap) · reduced motion ✓ · loading API ✗ *gap — prototype tanpa jaringan; tambahkan skeleton saat porting (T9)* · error API ✗ *gap — tambahkan banner error + retry (T9)*.

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
