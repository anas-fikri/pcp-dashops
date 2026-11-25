<?php

namespace App\Http\Controllers;

use App\Enums\PodNode;
use App\Http\Requests\PodFilterRequest;
use App\Services\Pod\PodDashboardService;
use App\Services\Pod\PodExcelExport;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PodDashboardController extends Controller
{
    public function __construct(private readonly PodDashboardService $pod) {}

    /** Level 1: summary (KPI, pohon status, aging, breakdown). */
    public function index(PodFilterRequest $request): View
    {
        $f = $request->filter();
        $tree = $this->pod->statusTree($f);

        return view('pod.index', [
            'f' => $f,
            'tree' => $tree,
            'aging' => $this->pod->agingMatrix($f),
            'colors' => $this->pod->colorCounts($f, PodNode::BelumBalik),
            'deliveredColors' => $this->pod->colorCounts($f, PodNode::Delivered),
            'perf' => $this->pod->returnPerformance($f),
            'branches' => $this->pod->breakdown($f, 'branch', config('pod.breakdown_limit')),
            'customers' => $this->pod->breakdown($f, 'customer', config('pod.breakdown_limit')),
            'options' => $this->pod->filterOptions(),
        ]);
    }

    /** Level 2: daftar AWB untuk sel summary yang diklik. */
    public function detail(PodFilterRequest $request): View
    {
        $f = $request->filter();

        return view('pod.detail', [
            'f' => $f,
            'rows' => $this->pod->detailQuery($f)->paginate(config('pod.detail_per_page'))->withQueryString(),
            'colors' => $this->pod->detailColorCounts($f),
            'options' => $this->pod->filterOptions(),
        ]);
    }

    public function export(PodFilterRequest $request, PodExcelExport $export): BinaryFileResponse
    {
        $f = $request->filter();
        $path = tempnam(sys_get_temp_dir(), 'pod');
        $export->write($f, $path);

        return response()->download($path, $export->filename($f), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
