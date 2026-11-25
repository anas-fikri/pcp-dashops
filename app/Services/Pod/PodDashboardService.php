<?php

namespace App\Services\Pod;

use App\Enums\PodNode;
use App\Enums\PodStatus;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

/**
 * Semua query dashboard POD. Prinsip: agregasi dilakukan di database
 * (GROUP BY), PHP hanya merakit hasil kecil. Setiap angka summary dapat
 * direproduksi oleh detailQuery() dengan filter yang sama (drill-down konsisten).
 */
class PodDashboardService
{
    private Connection $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db->connection();
    }

    public function aging(PodFilter $f): AgingSql
    {
        return new AgingSql($this->db, $f->asOf);
    }

    /** Query dasar + filter global (tanpa filter drill-down). */
    public function base(PodFilter $f): Builder
    {
        return $this->db->table('pod_shipments')
            ->where('shipment_date', '>=', $f->dateFrom->toDateTimeString())
            ->where('shipment_date', '<', $f->dateTo->addDay()->toDateTimeString())
            ->when($f->customer, fn (Builder $q, $v) => $q->where('customer_code', $v))
            ->when($f->branchType, fn (Builder $q, $v) => $q->where('dest_branch_type', $v))
            ->when($f->branch, fn (Builder $q, $v) => $q->where('dest_branch_code', $v))
            ->when($f->tier, fn (Builder $q, $v) => $q->where('dest_city_tier', $v))
            ->when($f->scope, fn (Builder $q, $v) => $q->where('service_scope', $v));
    }

    public function applyNode(Builder $q, PodNode $node): Builder
    {
        if (($required = $node->podRequired()) !== null) {
            $q->where('pod_required', $required);
        }
        if (($statuses = $node->statuses()) !== null) {
            $q->whereIn('pod_status', PodStatus::values($statuses));
        }

        return $q;
    }

    /**
     * Pohon status × scope.
     *
     * @return array<string, array{INTRACITY:int, DOMESTIK:int, total:int}> key = PodNode value
     */
    public function statusTree(PodFilter $f): array
    {
        $rows = $this->base($f)
            ->selectRaw('pod_required, pod_status, service_scope, COUNT(*) AS n')
            ->groupBy('pod_required', 'pod_status', 'service_scope')
            ->get();

        $tree = [];
        foreach (PodNode::tree() as $node) {
            $cell = ['INTRACITY' => 0, 'DOMESTIK' => 0, 'total' => 0];
            foreach ($rows as $r) {
                if ($this->rowInNode($node, (bool) $r->pod_required, $r->pod_status)) {
                    $cell[$r->service_scope] = ($cell[$r->service_scope] ?? 0) + (int) $r->n;
                    $cell['total'] += (int) $r->n;
                }
            }
            $tree[$node->value] = $cell;
        }

        return $tree;
    }

    /**
     * Matriks status × bucket aging untuk kiriman POD balik.
     *
     * @return array<string, array<int|string, int>> [status => [bucket => n, 'total' => n]]
     */
    public function agingMatrix(PodFilter $f): array
    {
        $bucket = $this->aging($f)->bucket();
        $rows = $this->base($f)
            ->where('pod_required', true)
            ->whereNotNull('pod_status')
            ->selectRaw("pod_status, {$bucket} AS bucket, COUNT(*) AS n")
            ->groupByRaw("pod_status, {$bucket}")
            ->get();

        $matrix = [];
        foreach (PodStatus::cases() as $s) {
            $matrix[$s->value] = array_fill_keys(array_keys(config('pod.aging_buckets')), 0) + ['total' => 0];
        }
        foreach ($rows as $r) {
            if (isset($matrix[$r->pod_status]) && $r->bucket !== null) {
                $matrix[$r->pod_status][(int) $r->bucket] += (int) $r->n;
                $matrix[$r->pod_status]['total'] += (int) $r->n;
            }
        }

        return $matrix;
    }

    /**
     * Jumlah kiriman per warna severity untuk sebuah node.
     *
     * @return array<string, int> [warna => n]
     */
    public function colorCounts(PodFilter $f, PodNode $node): array
    {
        $color = $this->aging($f)->color();
        $rows = $this->applyNode($this->base($f), $node)
            ->selectRaw("{$color} AS color, COUNT(*) AS n")
            ->groupByRaw($color)
            ->pluck('n', 'color');

        return collect(array_keys(config('pod.aging_colors')))
            ->mapWithKeys(fn ($c) => [$c => (int) ($rows[$c] ?? 0)])
            ->all();
    }

    /**
     * Performa pengembalian POD (node Sudah Balik) per scope.
     *
     * @return array<string, array{returned:int, avg_days:?float, on_time:int, on_time_pct:?float, sla:?int}>
     */
    public function returnPerformance(PodFilter $f): array
    {
        $aging = $this->aging($f);
        $rows = $this->applyNode($this->base($f), PodNode::SudahBalik)
            ->selectRaw("service_scope, COUNT(*) AS n, SUM({$aging->age()}) AS days, SUM({$aging->onTime()}) AS on_time")
            ->groupBy('service_scope')
            ->get()
            ->keyBy('service_scope');

        $out = [];
        $sumN = $sumDays = $sumOnTime = 0;
        foreach (array_keys(config('pod.scopes')) as $scope) {
            $r = $rows[$scope] ?? null;
            $n = (int) ($r->n ?? 0);
            $out[$scope] = $this->perfRow($n, (int) ($r->days ?? 0), (int) ($r->on_time ?? 0), config("pod.return_sla_days.{$scope}"));
            $sumN += $n;
            $sumDays += (int) ($r->days ?? 0);
            $sumOnTime += (int) ($r->on_time ?? 0);
        }
        $out['total'] = $this->perfRow($sumN, $sumDays, $sumOnTime, null);

        return $out;
    }

    /**
     * Breakdown backlog per cabang/perwakilan atau per customer, urut backlog terbesar.
     *
     * @param  'branch'|'customer'  $dimension
     */
    public function breakdown(PodFilter $f, string $dimension, ?int $limit = null): Collection
    {
        [$code, $name, $extra] = $dimension === 'branch'
            ? ['dest_branch_code', 'dest_branch_name', 'MAX(dest_branch_type) AS type']
            : ['customer_code', 'customer_name', "'' AS type"];

        $aging = $this->aging($f);
        $pending = $aging->inList(PodStatus::pending());
        $delivered = $aging->inList(PodStatus::deliveredPending());
        $returned = $aging->inList(PodStatus::returned());
        $ungu = $aging->rangeCondition(config('pod.aging_colors.ungu'));

        return $this->base($f)
            ->where('pod_required', true)
            ->selectRaw("{$code} AS code, MAX({$name}) AS name, {$extra},
                COUNT(*) AS balik,
                SUM(CASE WHEN pod_status IN ({$pending}) THEN 1 ELSE 0 END) AS belum_balik,
                SUM(CASE WHEN pod_status IN ({$delivered}) THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN pod_status IN ({$pending}) AND {$ungu} THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN pod_status IN ({$returned}) THEN 1 ELSE 0 END) AS sudah_balik,
                SUM(CASE WHEN pod_status IN ({$returned}) THEN {$aging->onTime()} ELSE 0 END) AS on_time")
            ->groupBy($code)
            ->orderByDesc('belum_balik')
            ->orderBy('code')
            ->when($limit, fn (Builder $q, $l) => $q->limit($l))
            ->get()
            ->map(function ($r) {
                foreach (['balik', 'belum_balik', 'delivered', 'overdue', 'sudah_balik', 'on_time'] as $k) {
                    $r->{$k} = (int) $r->{$k};
                }
                $r->return_pct = $r->balik ? round($r->sudah_balik / $r->balik * 100, 1) : null;
                $r->on_time_pct = $r->sudah_balik ? round($r->on_time / $r->sudah_balik * 100, 1) : null;

                return $r;
            });
    }

    /** Query daftar AWB sesuai filter global + drill-down, lengkap dengan age/bucket/color. */
    public function detailQuery(PodFilter $f): Builder
    {
        $aging = $this->aging($f);
        $q = $this->applyNode($this->base($f), $f->node)
            ->select([
                'awb', 'customer_code', 'customer_name', 'dest_branch_code', 'dest_branch_name',
                'dest_branch_type', 'origin_city', 'dest_city', 'dest_city_tier', 'service_scope',
                'pod_required', 'pod_status', 'shipment_date', 'delivered_at', 'pod_returned_at',
                'pod_status_at', 'recipient_name', 'remarks',
            ])
            ->selectRaw("{$aging->age()} AS age_days")
            ->selectRaw("{$aging->bucket()} AS bucket")
            ->selectRaw("{$aging->color()} AS color");

        return $this->applyDrill($q, $f)->orderBy($f->sort, $f->dir)->orderBy('awb');
    }

    /**
     * Distribusi warna untuk halaman detail: mengikuti node, bucket & pencarian aktif
     * (tidak termasuk filter warna itu sendiri), sehingga chip = jumlah baris bila diklik.
     *
     * @return array<string, int>
     */
    public function detailColorCounts(PodFilter $f): array
    {
        $color = $this->aging($f)->color();
        $q = $this->applyDrill($this->applyNode($this->base($f), $f->node), $f->with(['color' => null]));
        $rows = $q->selectRaw("{$color} AS color, COUNT(*) AS n")->groupByRaw($color)->pluck('n', 'color');

        return collect(array_keys(config('pod.aging_colors')))
            ->mapWithKeys(fn ($c) => [$c => (int) ($rows[$c] ?? 0)])
            ->all();
    }

    /** Filter drill-down (bucket, warna, cari AWB) di atas node. */
    private function applyDrill(Builder $q, PodFilter $f): Builder
    {
        $aging = $this->aging($f);
        if ($f->bucket !== null) {
            $q->whereRaw($aging->rangeCondition(config("pod.aging_buckets.{$f->bucket}")));
        }
        if ($f->color !== null) {
            $q->whereRaw($aging->rangeCondition(config("pod.aging_colors.{$f->color}")));
        }
        if ($f->search !== null) {
            $q->where('awb', 'like', '%'.addcslashes($f->search, '%_\\').'%');
        }

        return $q;
    }

    /** Opsi dropdown filter (dari data aktual). */
    public function filterOptions(): array
    {
        return [
            'customers' => $this->db->table('pod_shipments')
                ->selectRaw('customer_code AS code, MAX(customer_name) AS name')
                ->groupBy('customer_code')->orderBy('name')->get(),
            'branches' => $this->db->table('pod_shipments')
                ->selectRaw('dest_branch_code AS code, MAX(dest_branch_name) AS name, MAX(dest_branch_type) AS type')
                ->groupBy('dest_branch_code')->orderBy('name')->get(),
        ];
    }

    private function rowInNode(PodNode $node, bool $required, ?string $status): bool
    {
        if (($req = $node->podRequired()) !== null && $req !== $required) {
            return false;
        }
        if (($statuses = $node->statuses()) !== null) {
            return in_array($status, PodStatus::values($statuses), true);
        }

        return true;
    }

    private function perfRow(int $n, int $days, int $onTime, ?int $sla): array
    {
        return [
            'returned' => $n,
            'avg_days' => $n ? round($days / $n, 1) : null,
            'on_time' => $onTime,
            'on_time_pct' => $n ? round($onTime / $n * 100, 1) : null,
            'sla' => $sla,
        ];
    }
}
