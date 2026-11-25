<?php

/*
|--------------------------------------------------------------------------
| Dashboard Performance POD
|--------------------------------------------------------------------------
| Semua aturan bisnis yang bisa berubah (bucket aging, ambang warna, SLA)
| dipusatkan di sini supaya tidak tersebar di query/view.
| Lihat docs/pod-dashboard-spec.md untuk dasar keputusan.
*/

return [

    // Bucket aging per 5 hari x 6 (+1 bucket ">30" agar tidak ada data hilang).
    // 'max' => null berarti tanpa batas atas.
    'aging_buckets' => [
        1 => ['min' => 0,  'max' => 5,    'label' => '0–5'],
        2 => ['min' => 6,  'max' => 10,   'label' => '6–10'],
        3 => ['min' => 11, 'max' => 15,   'label' => '11–15'],
        4 => ['min' => 16, 'max' => 20,   'label' => '16–20'],
        5 => ['min' => 21, 'max' => 25,   'label' => '21–25'],
        6 => ['min' => 26, 'max' => 30,   'label' => '26–30'],
        7 => ['min' => 31, 'max' => null, 'label' => '>30'],
    ],

    // Warna severity berdasarkan umur (hari). Catatan asli: 1–3 hitam,
    // 4–5 oranye, 6–8 merah, >10 ungu. Gap hari ke-9 ditutup: 6–9 merah, >=10 ungu.
    'aging_colors' => [
        'hitam' => ['min' => 0,  'max' => 3,    'label' => '0–3 hari',  'hex' => '1F2328'],
        'oranye' => ['min' => 4,  'max' => 5,    'label' => '4–5 hari',  'hex' => 'D9730D'],
        'merah' => ['min' => 6,  'max' => 9,    'label' => '6–9 hari',  'hex' => 'C62828'],
        'ungu' => ['min' => 10, 'max' => null, 'label' => '≥10 hari',  'hex' => '7B1FA2'],
    ],

    // Target lead time POD kembali (hari sejak delivered) untuk KPI on-time return.
    'return_sla_days' => [
        'INTRACITY' => (int) env('POD_SLA_INTRACITY_DAYS', 5),
        'DOMESTIK' => (int) env('POD_SLA_DOMESTIK_DAYS', 10),
    ],

    // Periode default (berdasarkan tanggal kirim) bila filter tanggal kosong.
    'default_period_days' => (int) env('POD_DEFAULT_PERIOD_DAYS', 90),

    // Batas rentang filter tanggal, mencegah query full-scan tak terkendali.
    'max_period_days' => (int) env('POD_MAX_PERIOD_DAYS', 366),

    'detail_per_page' => 50,

    'breakdown_limit' => 15,

    // Batas baris sheet Detail pada export Excel.
    'export_max_rows' => (int) env('POD_EXPORT_MAX_ROWS', 100000),

    'scopes' => [
        'INTRACITY' => 'Intracity',
        'DOMESTIK' => 'Domestik',
    ],

    'branch_types' => [
        'PCP' => 'PCP (Cabang)',
        'PERWAKILAN' => 'Perwakilan',
    ],

    'city_tiers' => [1 => 'Tier 1', 2 => 'Tier 2', 3 => 'Tier 3'],
];
