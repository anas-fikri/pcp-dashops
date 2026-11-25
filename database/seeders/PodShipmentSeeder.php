<?php

namespace Database\Seeders;

use App\Enums\PodStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data SINTETIS untuk demo/pengembangan. Bukan data operasional.
 * Deterministik (seed tetap) supaya tampilan demo bisa direproduksi.
 */
class PodShipmentSeeder extends Seeder
{
    private const TOTAL = 8000;

    private const CUSTOMERS = [
        ['C001', 'PT Sinar Niaga Retail'],
        ['C002', 'PT Mitra Farma Nusantara'],
        ['C003', 'PT Garuda Elektronik'],
        ['C004', 'PT Bumi Agro Lestari'],
        ['C005', 'PT Cahaya Kosmetik'],
        ['C006', 'PT Prima Otomotif Parts'],
        ['C007', 'PT Samudra Fashion'],
        ['C008', 'CV Kopi Tani Jaya'],
        ['C009', 'PT Arta Perbankan Docs'],
        ['C010', 'PT Delta Kimia Industri'],
    ];

    // [kode, nama, tipe, kota, tier]
    private const BRANCHES = [
        ['JKT', 'PCP Jakarta',            'PCP',        'Jakarta',     1],
        ['SUB', 'PCP Surabaya',           'PCP',        'Surabaya',    1],
        ['BDG', 'PCP Bandung',            'PCP',        'Bandung',     1],
        ['MDN', 'PCP Medan',              'PCP',        'Medan',       1],
        ['SMG', 'PCP Semarang',           'PCP',        'Semarang',    2],
        ['MKS', 'PCP Makassar',           'PCP',        'Makassar',    2],
        ['DPS', 'Perwakilan Denpasar',    'PERWAKILAN', 'Denpasar',    2],
        ['PLB', 'Perwakilan Palembang',   'PERWAKILAN', 'Palembang',   2],
        ['PNK', 'Perwakilan Pontianak',   'PERWAKILAN', 'Pontianak',   3],
        ['JYP', 'Perwakilan Jayapura',    'PERWAKILAN', 'Jayapura',    3],
        ['KDI', 'Perwakilan Kendari',     'PERWAKILAN', 'Kendari',     3],
        ['TTE', 'Perwakilan Ternate',     'PERWAKILAN', 'Ternate',     3],
    ];

    private const ORIGINS = ['Jakarta', 'Surabaya', 'Bandung', 'Medan'];

    public function run(): void
    {
        mt_srand(20261125);
        $today = CarbonImmutable::today();
        $now = CarbonImmutable::now();
        $rows = [];

        for ($i = 1; $i <= self::TOTAL; $i++) {
            $intracity = mt_rand(1, 100) <= 45;
            [$code, $name, $type, $city, $tier] = $intracity
                ? self::BRANCHES[mt_rand(0, 5)]                          // intracity hanya di kota PCP besar
                : self::BRANCHES[mt_rand(0, count(self::BRANCHES) - 1)];
            $origin = $intracity ? $city : self::ORIGINS[mt_rand(0, 3)];
            [$custCode, $custName] = self::CUSTOMERS[$this->weighted([22, 15, 12, 10, 9, 8, 7, 7, 5, 5])];

            $shipped = $today->subDays(mt_rand(0, 120))->setTime(mt_rand(7, 18), mt_rand(0, 59));
            $required = mt_rand(1, 100) <= 80;

            // Lama transit
            $transit = $intracity ? mt_rand(0, 2) : mt_rand(1, 4) + ($tier === 3 ? mt_rand(1, 4) : 0);
            $stuck = mt_rand(1, 100) <= 3;
            $deliveredAt = $shipped->addDays($transit)->setTime(mt_rand(9, 20), mt_rand(0, 59));
            $delivered = ! $stuck && $deliveredAt->lessThanOrEqualTo($now);

            $status = null;
            $returnedAt = null;
            $statusAt = $shipped;

            if ($required) {
                if (! $delivered) {
                    $status = PodStatus::NotDelivered;
                } else {
                    $lead = $this->leadTime($intracity, $type, $tier);
                    $returnedAt = $deliveredAt->addDays($lead)->setTime(mt_rand(9, 17), mt_rand(0, 59));
                    if ($returnedAt->lessThanOrEqualTo($now)) {
                        $age = $returnedAt->diffInDays($now);
                        $status = $age > 30 && mt_rand(1, 100) <= 60
                            ? PodStatus::Arsip
                            : [PodStatus::Stpod, PodStatus::Podp][$this->weighted([60, 40])];
                        $statusAt = $returnedAt;
                    } else {
                        $returnedAt = null;
                        $status = PodStatus::deliveredPending()[$this->weighted([35, 30, 10, 25])];
                        $statusAt = $deliveredAt->addDays(mt_rand(0, max(0, (int) $deliveredAt->diffInDays($now))));
                    }
                }
            }

            $rows[] = [
                'awb' => sprintf('PCP%010d', 2611000000 + $i),
                'customer_code' => $custCode,
                'customer_name' => $custName,
                'dest_branch_code' => $code,
                'dest_branch_name' => $name,
                'dest_branch_type' => $type,
                'origin_city' => $origin,
                'dest_city' => $city,
                'dest_city_tier' => $tier,
                'service_scope' => $intracity ? 'INTRACITY' : 'DOMESTIK',
                'pod_required' => $required,
                'pod_status' => $status?->value,
                'shipment_date' => $shipped->toDateTimeString(),
                'delivered_at' => $delivered ? $deliveredAt->toDateTimeString() : null,
                'pod_returned_at' => $returnedAt?->toDateTimeString(),
                'pod_status_at' => ($statusAt->greaterThan($now) ? $now : $statusAt)->toDateTimeString(),
                'recipient_name' => $delivered ? 'Penerima '.mt_rand(100, 999) : null,
                'remarks' => $status === PodStatus::Redoc ? 'Cap/tanda tangan tidak lengkap' : null,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }

        DB::table('pod_shipments')->delete();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('pod_shipments')->insert($chunk);
        }
    }

    /** Lead time kembali POD (hari) dengan ekor panjang; perwakilan & tier 3 lebih lambat. */
    private function leadTime(bool $intracity, string $type, int $tier): int
    {
        $base = $intracity ? mt_rand(1, 6) : mt_rand(3, 12);
        if ($type === 'PERWAKILAN') {
            $base += mt_rand(0, 6);
        }
        if ($tier === 3) {
            $base += mt_rand(0, 8);
        }
        if (mt_rand(1, 100) <= 12) {
            $base += mt_rand(10, 40);  // kasus macet
        }

        return $base;
    }

    /** @param list<int> $weights */
    private function weighted(array $weights): int
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $idx => $w) {
            if (($r -= $w) <= 0) {
                return $idx;
            }
        }

        return 0;
    }
}
