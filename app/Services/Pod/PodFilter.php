<?php

namespace App\Services\Pod;

use App\Enums\PodNode;
use Carbon\CarbonImmutable;

/**
 * Filter dashboard yang sudah tervalidasi (lihat PodFilterRequest).
 * Immutable; gunakan with() untuk membuat link drill-down.
 */
final class PodFilter
{
    public const SORTABLE = [
        'age_days', 'awb', 'shipment_date', 'delivered_at', 'customer_name',
        'dest_branch_name', 'dest_city', 'pod_status', 'service_scope',
    ];

    public function __construct(
        public readonly CarbonImmutable $dateFrom,
        public readonly CarbonImmutable $dateTo,
        public readonly CarbonImmutable $asOf,
        public readonly ?string $customer = null,
        public readonly ?string $branchType = null,
        public readonly ?string $branch = null,
        public readonly ?int $tier = null,
        public readonly ?string $scope = null,
        public readonly PodNode $node = PodNode::All,
        public readonly ?int $bucket = null,
        public readonly ?string $color = null,
        public readonly ?string $search = null,
        public readonly string $sort = 'age_days',
        public readonly string $dir = 'desc',
    ) {}

    /** @param array<string, mixed> $v data hasil validasi */
    public static function fromArray(array $v): self
    {
        $asOf = isset($v['as_of']) ? CarbonImmutable::parse($v['as_of'])->startOfDay() : CarbonImmutable::today();
        $to = isset($v['date_to']) ? CarbonImmutable::parse($v['date_to'])->startOfDay() : $asOf;
        $from = isset($v['date_from'])
            ? CarbonImmutable::parse($v['date_from'])->startOfDay()
            : $to->subDays((int) config('pod.default_period_days') - 1);

        return new self(
            dateFrom: $from,
            dateTo: $to,
            asOf: $asOf,
            customer: $v['customer'] ?? null,
            branchType: $v['branch_type'] ?? null,
            branch: $v['branch'] ?? null,
            tier: isset($v['tier']) ? (int) $v['tier'] : null,
            scope: $v['scope'] ?? null,
            node: PodNode::tryFrom($v['node'] ?? '') ?? PodNode::All,
            bucket: isset($v['bucket']) ? (int) $v['bucket'] : null,
            color: $v['color'] ?? null,
            search: isset($v['q']) && trim($v['q']) !== '' ? trim($v['q']) : null,
            sort: in_array($v['sort'] ?? null, self::SORTABLE, true) ? $v['sort'] : 'age_days',
            dir: ($v['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
        );
    }

    /** Parameter URL; dipakai untuk link drill-down & export. @return array<string, scalar> */
    public function toQuery(): array
    {
        $isToday = $this->asOf->isSameDay(CarbonImmutable::today());

        return array_filter([
            'date_from' => $this->dateFrom->toDateString(),
            'date_to' => $this->dateTo->toDateString(),
            'as_of' => $isToday ? null : $this->asOf->toDateString(),
            'customer' => $this->customer,
            'branch_type' => $this->branchType,
            'branch' => $this->branch,
            'tier' => $this->tier,
            'scope' => $this->scope,
            'node' => $this->node === PodNode::All ? null : $this->node->value,
            'bucket' => $this->bucket,
            'color' => $this->color,
            'q' => $this->search,
            'sort' => $this->sort === 'age_days' ? null : $this->sort,
            'dir' => $this->dir === 'desc' ? null : $this->dir,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Hanya filter global (tanpa parameter drill-down). @return array<string, scalar> */
    public function globalQuery(): array
    {
        return array_diff_key($this->toQuery(), array_flip(['node', 'bucket', 'color', 'q', 'sort', 'dir']));
    }

    /**
     * Salinan filter dengan perubahan. Kunci: properti konstruktor (camelCase).
     *
     * @param  array<string, mixed>  $changes
     */
    public function with(array $changes): self
    {
        $props = get_object_vars($this);

        return new self(...array_merge($props, $changes));
    }

    /** Link detail untuk sebuah sel summary: filter global + override. @param array<string, mixed> $changes */
    public function detailUrl(array $changes = []): string
    {
        $base = $this->with([
            'node' => PodNode::All, 'bucket' => null, 'color' => null,
            'search' => null, 'sort' => 'age_days', 'dir' => 'desc',
        ]);

        return route('pod.detail', $base->with($changes)->toQuery());
    }

    public function hasDrillDown(): bool
    {
        return $this->node !== PodNode::All || $this->bucket !== null || $this->color !== null;
    }
}
