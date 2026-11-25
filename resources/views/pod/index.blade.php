@extends('layouts.app')

@php
    use App\Enums\PodNode;
    use App\Enums\PodStatus;
    $buckets = config('pod.aging_buckets');
    $balik = $tree['balik']['total'];
    $belum = $tree['belum_balik']['total'];
    $sudah = $tree['sudah_balik']['total'];
    $cell = fn (int $n, array $p) => $n
        ? '<a href="'.e($f->detailUrl($p)).'">'.pod_num($n).'</a>'
        : '0';
@endphp

@section('title', 'Summary')

@section('actions')
    <a class="btn light" href="{{ route('pod.export', $f->globalQuery()) }}">⬇ Export Excel</a>
@endsection

@section('content')
    @include('pod._filters', ['action' => route('pod.index')])

    {{-- KPI utama --}}
    <div class="kpis">
        <a class="kpi" href="{{ $f->detailUrl(['node' => PodNode::Balik]) }}">
            <div class="label">Total POD Balik</div>
            <div class="value">{{ pod_num($balik) }}</div>
            <div class="hint">dari {{ pod_num($tree['all']['total']) }} kiriman · {{ pod_num($tree['tidak_balik']['total']) }} tidak balik</div>
        </a>
        <a class="kpi" href="{{ $f->detailUrl(['node' => PodNode::SudahBalik]) }}">
            <div class="label">Sudah Balik</div>
            <div class="value">{{ pod_num($sudah) }}</div>
            <div class="hint">return rate {{ pod_pct($balik ? $sudah / $balik * 100 : null) }}</div>
        </a>
        <a class="kpi" href="{{ $f->detailUrl(['node' => PodNode::BelumBalik]) }}">
            <div class="label">Belum Balik</div>
            <div class="value">{{ pod_num($belum) }}</div>
            <div class="hint">{{ pod_num($tree['not_delivered']['total']) }} belum terkirim · {{ pod_num($tree['delivered']['total']) }} terkirim</div>
        </a>
        <a class="kpi" href="{{ $f->detailUrl(['node' => PodNode::Delivered]) }}">
            <div class="label">Delivered, POD belum kembali</div>
            <div class="value">{{ pod_num($tree['delivered']['total']) }}</div>
            <div class="hint">fokus penagihan POD ke cabang/perwakilan</div>
        </a>
        <a class="kpi alert" href="{{ $f->detailUrl(['node' => PodNode::BelumBalik, 'color' => 'ungu']) }}">
            <div class="label">Overdue ≥10 hari (ungu)</div>
            <div class="value c-ungu">{{ pod_num($colors['ungu']) }}</div>
            <div class="hint">{{ pod_pct($belum ? $colors['ungu'] / $belum * 100 : null) }} dari belum balik</div>
        </a>
        <a class="kpi" href="{{ $f->detailUrl(['node' => PodNode::SudahBalik]) }}">
            <div class="label">On-time Return</div>
            <div class="value">{{ pod_pct($perf['total']['on_time_pct']) }}</div>
            <div class="hint">rata-rata {{ pod_num($perf['total']['avg_days'], 1) }} hari · SLA {{ implode('/', config('pod.return_sla_days')) }} hari</div>
        </a>
    </div>

    {{-- Distribusi warna belum balik --}}
    <div class="legend">
        <span class="muted" style="align-self:center">Belum balik per warna umur:</span>
        @foreach (config('pod.aging_colors') as $key => $c)
            <a class="chip" href="{{ $f->detailUrl(['node' => PodNode::BelumBalik, 'color' => $key]) }}">
                <span class="dot bg-{{ $key }}"></span> {{ ucfirst($key) }} <span class="muted">{{ $c['label'] }}</span> <b>{{ pod_num($colors[$key]) }}</b>
            </a>
        @endforeach
    </div>

    <div class="grid two">
        {{-- Pohon status × scope --}}
        <section class="card">
            <h2>Pohon Status POD</h2>
            <p class="sub">Struktur sesuai alur: POD → Balik → Belum/Sudah Balik → status. Klik angka untuk daftar AWB.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Status</th><th>Intracity</th><th>Domestik</th><th>Total</th><th>% POD Balik</th></tr></thead>
                    <tbody>
                    @foreach (PodNode::tree() as $node)
                        @php $c = $tree[$node->value]; @endphp
                        <tr class="lvl{{ $node->depth() }}">
                            <td style="padding-left: {{ 10 + $node->depth() * 18 }}px">{{ $node->label() }}</td>
                            @foreach (array_keys(config('pod.scopes')) as $scope)
                                <td @class(['zero' => ! $c[$scope]])>{!! $cell($c[$scope], ['node' => $node, 'scope' => $scope]) !!}</td>
                            @endforeach
                            <td @class(['zero' => ! $c['total']])><b>{!! $cell($c['total'], ['node' => $node]) !!}</b></td>
                            <td class="muted">{{ $node->podRequired() && $node !== PodNode::Balik ? pod_pct($balik ? $c['total'] / $balik * 100 : null) : '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Performa pengembalian --}}
        <section class="card">
            <h2>Performa Pengembalian POD</h2>
            <p class="sub">Lead time = tanggal POD kembali − tanggal delivered. On-time bila ≤ SLA scope.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Scope</th><th>Sudah Balik</th><th>Rata-rata hari</th><th>SLA</th><th>On-time</th><th class="left">% On-time</th></tr></thead>
                    <tbody>
                    @foreach ($perf as $scope => $p)
                        <tr @class(['total' => $scope === 'total'])>
                            <td>{{ config("pod.scopes.$scope", 'Total') }}</td>
                            <td>{!! $cell($p['returned'], ['node' => PodNode::SudahBalik] + ($scope === 'total' ? [] : ['scope' => $scope])) !!}</td>
                            <td>{{ pod_num($p['avg_days'], 1) }}</td>
                            <td>{{ $p['sla'] ? $p['sla'].' hari' : '' }}</td>
                            <td>{{ pod_num($p['on_time']) }}</td>
                            <td class="left">
                                <div style="display:flex;gap:8px;align-items:center">
                                    <div class="bar"><span style="width: {{ $p['on_time_pct'] ?? 0 }}%"></span></div>
                                    {{ pod_pct($p['on_time_pct']) }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </section>
    </div>

    <section class="card" style="margin-top:16px">
        <h2>Aging Belum Balik (per 5 hari)</h2>
        <p class="sub">Umur = hari sejak delivered (untuk yang sudah terkirim) atau sejak tanggal kirim (Not Yet Delivered). Garis warna di header = severity minimum bucket.</p>
        @include('pod._aging_table', ['statuses' => PodStatus::pending(), 'parent' => PodNode::BelumBalik, 'subtotal' => true])
    </section>

    <section class="card" style="margin-top:16px">
        <h2>Lead Time Sudah Balik</h2>
        <p class="sub">Jumlah AWB per rentang hari dari delivered sampai POD kembali (warna = severity lead time).</p>
        @include('pod._aging_table', ['statuses' => PodStatus::returned(), 'parent' => PodNode::SudahBalik])
    </section>

    <div class="grid two" style="margin-top:16px">
        @foreach (['branch' => ['Per Cabang / Perwakilan', $branches], 'customer' => ['Per Customer', $customers]] as $dim => [$title, $list])
            <section class="card">
                <h2>{{ $title }}</h2>
                <p class="sub">{{ config('pod.breakdown_limit') }} backlog terbesar. Klik nama untuk memfilter dashboard; lengkapnya di Excel.</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>{{ $dim === 'branch' ? 'Cabang' : 'Customer' }}</th><th>POD Balik</th><th>Belum Balik</th><th>Overdue</th><th>% Kembali</th><th>% On-time</th></tr></thead>
                        <tbody>
                        @forelse ($list as $r)
                            @php $scoped = $f->with($dim === 'branch' ? ['branch' => $r->code] : ['customer' => $r->code]); @endphp
                            <tr>
                                <td><a href="{{ route('pod.index', $scoped->globalQuery()) }}">{{ $r->name }}</a>
                                    @if ($r->type) <span class="tag">{{ $r->type === 'PCP' ? 'PCP' : 'Perwakilan' }}</span> @endif</td>
                                <td>{!! $cell($r->balik, ['node' => PodNode::Balik] + ($dim === 'branch' ? ['branch' => $r->code] : ['customer' => $r->code])) !!}</td>
                                <td>{!! $cell($r->belum_balik, ['node' => PodNode::BelumBalik] + ($dim === 'branch' ? ['branch' => $r->code] : ['customer' => $r->code])) !!}</td>
                                <td class="{{ $r->overdue ? 'c-ungu' : 'zero' }}">{!! $cell($r->overdue, ['node' => PodNode::BelumBalik, 'color' => 'ungu'] + ($dim === 'branch' ? ['branch' => $r->code] : ['customer' => $r->code])) !!}</td>
                                <td>{{ pod_pct($r->return_pct) }}</td>
                                <td>{{ pod_pct($r->on_time_pct) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">Tidak ada data untuk filter ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
@endsection
