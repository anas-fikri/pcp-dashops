<?php

namespace App\Services\Pod;

use App\Enums\PodNode;
use App\Enums\PodStatus;
use Carbon\CarbonImmutable;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Export "Excel Performance POD": Summary, Aging, Return Performance,
 * Per Cabang, Per Customer, Detail. Ditulis secara streaming (memori rendah).
 */
class PodExcelExport
{
    private Style $title;

    private Style $header;

    private Style $bold;

    private Style $dateStyle;

    /** @var array<string, Style> */
    private array $colorStyles = [];

    public function __construct(private readonly PodDashboardService $pod) {}

    public function filename(PodFilter $f): string
    {
        return sprintf('performance-pod_%s_%s.xlsx', $f->dateFrom->format('Ymd'), $f->dateTo->format('Ymd'));
    }

    public function write(PodFilter $f, string $path): void
    {
        $this->title = (new Style)->withFontBold(true)->withFontSize(14);
        $this->header = (new Style)->withFontBold(true)->withFontColor(Color::WHITE)->withBackgroundColor('1F3A5F');
        $this->bold = (new Style)->withFontBold(true);
        $this->dateStyle = (new Style)->withFormat('dd/mm/yyyy hh:mm');
        foreach (config('pod.aging_colors') as $key => $c) {
            $this->colorStyles[$key] = (new Style)->withFontBold(true)->withFontColor($c['hex']);
        }

        $writer = new Writer(new Options(properties: new Properties(
            title: 'Performance POD', application: 'PCP DashOps', creator: 'PCP DashOps', lastModifiedBy: 'PCP DashOps',
        )));
        $writer->openToFile($path);

        $this->summarySheet($writer, $f);
        $this->agingSheet($writer, $f);
        $this->breakdownSheet($writer, $f, 'branch', 'Per Cabang');
        $this->breakdownSheet($writer, $f, 'customer', 'Per Customer');
        $this->detailSheet($writer, $f);

        $writer->close();
    }

    private function summarySheet(Writer $w, PodFilter $f): void
    {
        $w->getCurrentSheet()->setName('Summary');
        $w->getCurrentSheet()->setColumnWidth(38, 1);
        $w->getCurrentSheet()->setColumnWidthForRange(14, 2, 6);

        $this->row($w, ['Performance POD'], $this->title);
        foreach ($this->filterLines($f) as $line) {
            $this->row($w, $line);
        }
        $this->row($w, []);

        $tree = $this->pod->statusTree($f);
        $this->row($w, ['Status POD', 'Intracity', 'Domestik', 'Total', '% dari POD Balik'], $this->header);
        $balik = $tree[PodNode::Balik->value]['total'];
        foreach (PodNode::tree() as $node) {
            $c = $tree[$node->value];
            $pct = $node->podRequired() && $node !== PodNode::Balik && $balik ? round($c['total'] / $balik * 100, 1) : null;
            $this->row($w, [str_repeat('    ', $node->depth()).$node->label(), $c['INTRACITY'], $c['DOMESTIK'], $c['total'], $pct],
                $node->depth() <= 2 ? $this->bold : null);
        }

        $this->row($w, []);
        $this->row($w, ['Performa Pengembalian (Sudah Balik)', 'Jumlah', 'Rata-rata hari', 'On-time', '% On-time', 'SLA (hari)'], $this->header);
        foreach ($this->pod->returnPerformance($f) as $scope => $p) {
            $this->row($w, [config("pod.scopes.{$scope}", 'Total'), $p['returned'], $p['avg_days'], $p['on_time'], $p['on_time_pct'], $p['sla']]);
        }

        $this->row($w, []);
        $this->row($w, ['Belum Balik per Warna', 'Jumlah'], $this->header);
        foreach ($this->pod->colorCounts($f, PodNode::BelumBalik) as $key => $n) {
            $this->row($w, [ucfirst($key).' ('.config("pod.aging_colors.{$key}.label").')', $n], $this->colorStyles[$key]);
        }
    }

    private function agingSheet(Writer $w, PodFilter $f): void
    {
        $sheet = $w->addNewSheetAndMakeItCurrent()->setName('Aging');
        $sheet->setColumnWidth(30, 1);
        $buckets = config('pod.aging_buckets');
        $matrix = $this->pod->agingMatrix($f);

        $head = ['Status', ...array_map(fn ($b) => $b['label'].' hari', $buckets), 'Total'];
        $this->row($w, ['Aging Belum Balik (umur sejak terkirim; belum terkirim: sejak tanggal kirim)'], $this->bold);
        $this->row($w, $head, $this->header);
        $this->matrixRows($w, $matrix, PodStatus::pending(), 'Total Belum Balik');

        $this->row($w, []);
        $this->row($w, ['Lead Time Sudah Balik (hari dari delivered s/d POD kembali)'], $this->bold);
        $this->row($w, $head, $this->header);
        $this->matrixRows($w, $matrix, PodStatus::returned(), 'Total Sudah Balik');
    }

    /** @param list<PodStatus> $statuses */
    private function matrixRows(Writer $w, array $matrix, array $statuses, string $totalLabel): void
    {
        $keys = [...array_keys(config('pod.aging_buckets')), 'total'];
        $sum = array_fill_keys($keys, 0);
        foreach ($statuses as $s) {
            $vals = array_map(fn ($k) => $matrix[$s->value][$k], $keys);
            foreach ($keys as $i => $k) {
                $sum[$k] += $vals[$i];
            }
            $this->row($w, [$s->label(), ...$vals]);
        }
        $this->row($w, [$totalLabel, ...array_values($sum)], $this->bold);
    }

    private function breakdownSheet(Writer $w, PodFilter $f, string $dim, string $name): void
    {
        $sheet = $w->addNewSheetAndMakeItCurrent()->setName($name);
        $sheet->setColumnWidth(32, 2);
        $sheet->setSheetView(new SheetView(freezeRow: 2));
        $this->row($w, [
            'Kode', $dim === 'branch' ? 'Cabang / Perwakilan' : 'Customer', 'Tipe', 'POD Balik', 'Belum Balik',
            'Delivered belum balik', 'Overdue (≥10 hari)', 'Sudah Balik', '% Kembali', 'On-time', '% On-time',
        ], $this->header);

        foreach ($this->pod->breakdown($f, $dim) as $r) {
            $this->row($w, [$r->code, $r->name, $r->type, $r->balik, $r->belum_balik, $r->delivered,
                $r->overdue, $r->sudah_balik, $r->return_pct, $r->on_time, $r->on_time_pct]);
        }
    }

    private function detailSheet(Writer $w, PodFilter $f): void
    {
        $sheet = $w->addNewSheetAndMakeItCurrent()->setName('Detail');
        $sheet->setSheetView(new SheetView(freezeRow: 2));
        $sheet->setColumnWidthForRange(16, 1, 18);

        // Tanpa drill-down: detail = semua POD Balik (yang dimonitor).
        $df = $f->node === PodNode::All ? $f->with(['node' => PodNode::Balik]) : $f;
        $head = ['AWB', 'Customer', 'Kode Cust', 'Cabang/Perwakilan', 'Tipe', 'Asal', 'Tujuan', 'Tier', 'Scope',
            'POD Balik', 'Status POD', 'Grup', 'Tgl Kirim', 'Tgl Delivered', 'Tgl POD Kembali', 'Umur (hari)', 'Warna', 'Keterangan'];
        $this->row($w, $head, $this->header);

        $max = (int) config('pod.export_max_rows');
        $n = 0;
        foreach ($this->pod->detailQuery($df)->lazy(2000) as $r) {
            if (++$n > $max) {
                $this->row($w, ["Dipotong pada {$max} baris. Persempit filter untuk data lengkap."], $this->bold);
                break;
            }
            $status = $r->pod_status ? PodStatus::from($r->pod_status) : null;
            $cells = [
                $r->awb, $r->customer_name, $r->customer_code, $r->dest_branch_name, $r->dest_branch_type,
                $r->origin_city, $r->dest_city, (int) $r->dest_city_tier, config("pod.scopes.{$r->service_scope}"),
                $r->pod_required ? 'Ya' : 'Tidak', $status?->label() ?? '-', $this->groupLabel($status),
                $this->date($r->shipment_date), $this->date($r->delivered_at), $this->date($r->pod_returned_at),
                $r->age_days === null ? null : (int) $r->age_days, $r->color ? ucfirst($r->color) : null, $r->remarks,
            ];
            $out = [];
            foreach ($cells as $i => $v) {
                $style = match (true) {
                    in_array($i, [12, 13, 14], true) && $v !== null => $this->dateStyle,
                    $i === 15 && $r->color !== null => $this->colorStyles[$r->color],
                    default => null,
                };
                $out[] = Cell::fromValue($v, $style);
            }
            $row = new Row($out);
            $w->addRow($row);
        }

        $sheet->setAutoFilter(new AutoFilter(0, 1, count($head) - 1, max(1, min($n, $max) + 1)));
    }

    private function groupLabel(?PodStatus $s): string
    {
        return match (true) {
            $s === null => 'Tidak Balik',
            $s->isReturned() => 'Sudah Balik',
            $s === PodStatus::NotDelivered => 'Belum Balik - Not Delivered',
            default => 'Belum Balik - Delivered',
        };
    }

    /** @return list<list<string>> */
    private function filterLines(PodFilter $f): array
    {
        $lines = [
            ['Periode kirim', $f->dateFrom->format('d M Y').' s/d '.$f->dateTo->format('d M Y')],
            ['Posisi per', $f->asOf->format('d M Y')],
            ['Dibuat', CarbonImmutable::now()->format('d M Y H:i')],
        ];
        $map = [
            'Customer' => $f->customer, 'Tipe cabang' => $f->branchType, 'Cabang' => $f->branch,
            'Tier kota' => $f->tier, 'Scope' => $f->scope,
            'Drill-down' => $f->node !== PodNode::All ? $f->node->label() : null,
            'Bucket aging' => $f->bucket ? config("pod.aging_buckets.{$f->bucket}.label").' hari' : null,
            'Warna' => $f->color,
        ];
        foreach ($map as $k => $v) {
            if ($v !== null) {
                $lines[] = [$k, (string) $v];
            }
        }

        return $lines;
    }

    private function date(?string $v): ?\DateTimeImmutable
    {
        return $v ? new \DateTimeImmutable($v) : null;
    }

    /** @param list<mixed> $values */
    private function row(Writer $w, array $values, ?Style $style = null): void
    {
        $w->addRow(new Row(array_map(fn ($v) => Cell::fromValue($v, $style), $values)));
    }
}
