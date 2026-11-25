<?php

namespace Tests\Feature;

use App\Enums\PodNode;
use App\Enums\PodStatus;
use App\Models\PodShipment;
use App\Services\Pod\PodDashboardService;
use App\Services\Pod\PodFilter;
use Carbon\CarbonImmutable;
use Database\Seeders\PodShipmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class PodDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const AS_OF = '2026-06-30';

    private function asOf(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::AS_OF);
    }

    private function filter(array $extra = []): PodFilter
    {
        return PodFilter::fromArray(['as_of' => self::AS_OF, 'date_from' => '2026-01-01', 'date_to' => self::AS_OF] + $extra);
    }

    private function service(): PodDashboardService
    {
        return app(PodDashboardService::class);
    }

    private function make(PodStatus $status, int $days, array $attrs = []): PodShipment
    {
        return PodShipment::factory()->withStatus($status, $days, $this->asOf())->create($attrs);
    }

    public function test_aging_bucket_boundaries_follow_five_day_steps(): void
    {
        foreach ([0, 5, 6, 10, 30, 31] as $d) {
            $this->make(PodStatus::Upload, $d);
        }

        $row = $this->service()->agingMatrix($this->filter())['UPLOAD'];

        $this->assertSame([1 => 2, 2 => 2, 3 => 0, 4 => 0, 5 => 0, 6 => 1, 7 => 1, 'total' => 6], $row);
    }

    public function test_color_thresholds_cover_every_day_including_day_nine(): void
    {
        foreach ([3, 4, 5, 6, 9, 10] as $d) {
            $this->make(PodStatus::Podc, $d);
        }

        $colors = $this->service()->colorCounts($this->filter(), PodNode::BelumBalik);

        $this->assertSame(['hitam' => 1, 'oranye' => 2, 'merah' => 2, 'ungu' => 1], $colors);
    }

    public function test_not_delivered_age_counts_from_shipment_date(): void
    {
        $this->make(PodStatus::NotDelivered, 12);

        $this->assertSame(1, $this->service()->agingMatrix($this->filter())['NOT_DELIVERED'][3]);
    }

    public function test_status_tree_is_internally_consistent(): void
    {
        $this->make(PodStatus::NotDelivered, 2);
        $this->make(PodStatus::MasihDiPelanggan, 4, ['service_scope' => 'DOMESTIK']);
        $this->make(PodStatus::Redoc, 7);
        $this->make(PodStatus::Stpod, 3);
        $this->make(PodStatus::Arsip, 12, ['service_scope' => 'DOMESTIK']);
        PodShipment::factory()->tidakBalik()->create(['shipment_date' => '2026-06-01 10:00:00']);

        $t = $this->service()->statusTree($this->filter());

        $this->assertSame(6, $t['all']['total']);
        $this->assertSame(1, $t['tidak_balik']['total']);
        $this->assertSame(5, $t['balik']['total']);
        $this->assertSame(3, $t['belum_balik']['total']);
        $this->assertSame(2, $t['delivered']['total']);
        $this->assertSame(2, $t['sudah_balik']['total']);
        $this->assertSame(1, $t['sudah_balik']['DOMESTIK']);
        $this->assertSame($t['balik']['total'], $t['belum_balik']['total'] + $t['sudah_balik']['total']);
    }

    public function test_return_performance_uses_lead_time_and_scope_sla(): void
    {
        $this->make(PodStatus::Stpod, 5, ['service_scope' => 'INTRACITY']);   // SLA 5 → on time
        $this->make(PodStatus::Podp, 6, ['service_scope' => 'INTRACITY']);    // late
        $this->make(PodStatus::Arsip, 10, ['service_scope' => 'DOMESTIK']);   // SLA 10 → on time
        $this->make(PodStatus::Stpod, 11, ['service_scope' => 'DOMESTIK']);   // late

        $p = $this->service()->returnPerformance($this->filter());

        $this->assertSame(['returned' => 2, 'avg_days' => 5.5, 'on_time' => 1, 'on_time_pct' => 50.0, 'sla' => 5], $p['INTRACITY']);
        $this->assertSame(2, $p['DOMESTIK']['returned']);
        $this->assertSame(1, $p['DOMESTIK']['on_time']);
        $this->assertSame(8.0, $p['total']['avg_days']);
    }

    public function test_global_filters_restrict_results(): void
    {
        $this->make(PodStatus::Upload, 1, ['customer_code' => 'C001', 'dest_city_tier' => 1, 'dest_branch_code' => 'JKT', 'dest_branch_type' => 'PCP']);
        $this->make(PodStatus::Upload, 1, ['customer_code' => 'C002', 'dest_city_tier' => 3, 'dest_branch_code' => 'JYP', 'dest_branch_type' => 'PERWAKILAN', 'service_scope' => 'DOMESTIK']);

        $count = fn (array $x) => $this->service()->statusTree($this->filter($x))['balik']['total'];

        $this->assertSame(2, $count([]));
        $this->assertSame(1, $count(['customer' => 'C002']));
        $this->assertSame(1, $count(['tier' => 3]));
        $this->assertSame(1, $count(['branch' => 'JKT']));
        $this->assertSame(1, $count(['branch_type' => 'PERWAKILAN']));
        $this->assertSame(1, $count(['scope' => 'INTRACITY']));
        $this->assertSame(0, $count(['customer' => 'C001', 'scope' => 'DOMESTIK']));
    }

    public function test_every_summary_number_equals_its_drill_down_row_count(): void
    {
        $this->seed(PodShipmentSeeder::class);
        $svc = $this->service();
        $f = PodFilter::fromArray([]);

        $tree = $svc->statusTree($f);
        foreach (PodNode::tree() as $node) {
            foreach ([null, 'INTRACITY', 'DOMESTIK'] as $scope) {
                $expected = $tree[$node->value][$scope ?? 'total'];
                $actual = $svc->detailQuery($f->with(['node' => $node, 'scope' => $scope]))->count();
                $this->assertSame($expected, $actual, "node={$node->value} scope={$scope}");
            }
        }

        $aging = $svc->agingMatrix($f);
        foreach (PodStatus::cases() as $status) {
            foreach (array_keys(config('pod.aging_buckets')) as $b) {
                $actual = $svc->detailQuery($f->with(['node' => PodNode::forStatus($status), 'bucket' => $b]))->count();
                $this->assertSame($aging[$status->value][$b], $actual, "status={$status->value} bucket={$b}");
            }
        }

        $colors = $svc->colorCounts($f, PodNode::BelumBalik);
        foreach ($colors as $color => $n) {
            $this->assertSame($n, $svc->detailQuery($f->with(['node' => PodNode::BelumBalik, 'color' => $color]))->count());
        }
        $this->assertSame($tree['belum_balik']['total'], array_sum($colors));

        foreach ($svc->breakdown($f, 'branch') as $r) {
            $bf = $f->with(['branch' => $r->code]);
            $this->assertSame($r->belum_balik, $svc->detailQuery($bf->with(['node' => PodNode::BelumBalik]))->count());
            $this->assertSame($r->overdue, $svc->detailQuery($bf->with(['node' => PodNode::BelumBalik, 'color' => 'ungu']))->count());
        }
    }

    public function test_pages_render_and_link_to_detail(): void
    {
        $awb = $this->make(PodStatus::Podc, 7)->awb;

        $this->get('/pod?as_of='.self::AS_OF.'&date_from=2026-01-01&date_to='.self::AS_OF)
            ->assertOk()
            ->assertSee('Pohon Status POD')
            ->assertSee('node=podc', false);

        $this->get('/pod/detail?as_of='.self::AS_OF.'&date_from=2026-01-01&date_to='.self::AS_OF.'&node=podc&bucket=2')
            ->assertOk()
            ->assertSee($awb)
            ->assertSee('1 AWB');

        $this->get('/pod/detail?as_of='.self::AS_OF.'&date_from=2026-01-01&date_to='.self::AS_OF.'&node=podc&bucket=1')
            ->assertOk()
            ->assertDontSee($awb);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->get('/pod?scope=XX')->assertSessionHasErrors('scope');
        $this->get('/pod?sort=password')->assertSessionHasErrors('sort');
        $this->get('/pod/detail?node=drop_table')->assertSessionHasErrors('node');
        $this->get('/pod?date_from=2026-06-10&date_to=2026-06-01&as_of=2026-06-30')->assertSessionHasErrors('date_from');
        $this->get('/pod?date_from=2024-01-01&date_to=2026-06-01&as_of=2026-06-30')->assertSessionHasErrors('date_from');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->make(PodStatus::Upload, 1, ['awb' => 'PCP0001']);

        $this->assertSame(0, $this->service()->detailQuery($this->filter(['q' => '%']))->count());
        $this->assertSame(1, $this->service()->detailQuery($this->filter(['q' => 'P0001']))->count());
    }

    public function test_export_produces_workbook_with_all_sheets(): void
    {
        $this->make(PodStatus::Redoc, 12, ['awb' => 'PCPEXPORT01']);
        $this->make(PodStatus::Stpod, 3);

        $response = $this->get('/pod/export?as_of='.self::AS_OF.'&date_from=2026-01-01&date_to='.self::AS_OF.'&node=belum_balik');
        $response->assertOk();
        $this->assertStringContainsString('performance-pod_20260101_20260630.xlsx', $response->headers->get('content-disposition'));

        $reader = new Reader;
        $reader->open($response->getFile()->getPathname());
        $sheets = [];
        $detail = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet->getName();
            if ($sheet->getName() === 'Detail') {
                foreach ($sheet->getRowIterator() as $row) {
                    $detail[] = $row->toArray();
                }
            }
        }
        $reader->close();

        $this->assertSame(['Summary', 'Aging', 'Per Cabang', 'Per Customer', 'Detail'], $sheets);
        $this->assertCount(2, $detail); // header + 1 AWB belum balik
        $this->assertSame('PCPEXPORT01', $detail[1][0]);
        $this->assertSame(12, $detail[1][15]);
        $this->assertSame('Ungu', $detail[1][16]);
    }
}
