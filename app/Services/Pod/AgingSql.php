<?php

namespace App\Services\Pod;

use App\Enums\PodStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;

/**
 * Ekspresi SQL umur/bucket/warna yang portabel lintas driver
 * (sqlite untuk dev/test, sqlsrv / mysql / pgsql untuk produksi).
 *
 * Definisi umur (age_days), dalam hari kalender:
 *  - Belum balik & belum terkirim : as_of − shipment_date
 *  - Belum balik & sudah terkirim : as_of − delivered_at
 *  - Sudah balik                  : pod_returned_at − delivered_at  (lead time kembali)
 *  - Tidak balik                  : NULL
 */
final class AgingSql
{
    public function __construct(
        private readonly Connection $db,
        private readonly CarbonImmutable $asOf,
    ) {}

    public function age(): string
    {
        $asOf = $this->db->getPdo()->quote($this->asOf->toDateString());
        $returned = $this->inList(PodStatus::returned());

        $lead = $this->dayDiff('delivered_at', 'pod_returned_at');
        $outstanding = $this->dayDiff('COALESCE(delivered_at, shipment_date)', $asOf);

        $false = $this->db->getDriverName() === 'pgsql' ? 'false' : '0';

        return "(CASE WHEN pod_required = {$false} OR pod_status IS NULL THEN NULL
            WHEN pod_status IN ({$returned}) THEN {$lead}
            ELSE {$outstanding} END)";
    }

    public function bucket(): string
    {
        return $this->rangeCase(config('pod.aging_buckets'), fn ($key) => (string) (int) $key);
    }

    public function color(): string
    {
        return $this->rangeCase(config('pod.aging_colors'), fn ($key) => "'".preg_replace('/\W/', '', $key)."'");
    }

    /** WHERE untuk rentang [min, max] pada age. @param array{min:int,max:?int} $range */
    public function rangeCondition(array $range): string
    {
        $age = $this->age();
        // Rentang pertama (min <= 0) juga menampung umur negatif (mis. as_of di masa lalu),
        // konsisten dengan rangeCase() sehingga angka summary = jumlah baris detail.
        $conds = [$range['min'] <= 0 ? "{$age} IS NOT NULL" : "{$age} >= ".(int) $range['min']];
        if ($range['max'] !== null) {
            $conds[] = "{$age} <= ".(int) $range['max'];
        }

        return implode(' AND ', $conds);
    }

    /** 1 bila POD kembali dalam SLA scope-nya, else 0. */
    public function onTime(): string
    {
        $age = $this->age();
        $cases = collect(config('pod.return_sla_days'))
            ->map(fn ($days, $scope) => "WHEN service_scope = '".preg_replace('/\W/', '', $scope)."' AND {$age} <= ".(int) $days.' THEN 1')
            ->implode(' ');

        return "(CASE {$cases} ELSE 0 END)";
    }

    /** Selisih hari kalender (to − from). */
    public function dayDiff(string $from, string $to): string
    {
        return match ($this->db->getDriverName()) {
            'sqlite' => "CAST(julianday(date({$to})) - julianday(date({$from})) AS INTEGER)",
            'mysql', 'mariadb' => "DATEDIFF({$to}, {$from})",
            'sqlsrv' => "DATEDIFF(day, {$from}, {$to})",
            'pgsql' => "(CAST({$to} AS date) - CAST({$from} AS date))",
            default => throw new \RuntimeException('Driver DB tidak didukung: '.$this->db->getDriverName()),
        };
    }

    /** @param list<PodStatus> $statuses */
    public function inList(array $statuses): string
    {
        return implode(',', array_map(fn (PodStatus $s) => "'{$s->value}'", $statuses));
    }

    /** @param array<string|int, array{min:int,max:?int}> $ranges */
    private function rangeCase(array $ranges, callable $literal): string
    {
        $age = $this->age();
        $whens = [];
        foreach ($ranges as $key => $r) {
            $cond = $r['max'] === null ? "{$age} >= ".(int) $r['min'] : "{$age} <= ".(int) $r['max'];
            $whens[] = "WHEN {$cond} THEN ".$literal($key);
        }

        return "(CASE WHEN {$age} IS NULL THEN NULL ".implode(' ', $whens).' END)';
    }
}
