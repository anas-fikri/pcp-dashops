@extends('layouts.app')

@php
    use App\Enums\PodNode;
    use App\Enums\PodStatus;
    $sortUrl = function (string $col) use ($f) {
        $dir = $f->sort === $col && $f->dir === 'desc' ? 'asc' : 'desc';
        return route('pod.detail', $f->with(['sort' => $col, 'dir' => $dir])->toQuery());
    };
    $arrow = fn (string $col) => $f->sort === $col ? ($f->dir === 'desc' ? ' ▼' : ' ▲') : '';
    $date = fn (?string $v) => $v ? \Carbon\CarbonImmutable::parse($v)->format('d/m/Y') : '–';
    $removeUrl = fn (array $c) => route('pod.detail', $f->with($c)->toQuery());
@endphp

@section('title', 'Detail '.$f->node->label())

@section('actions')
    <a class="btn light" href="{{ route('pod.index', $f->globalQuery()) }}">← Summary</a>
    <a class="btn light" href="{{ route('pod.export', $f->toQuery()) }}">⬇ Export Excel</a>
@endsection

@section('content')
    @include('pod._filters', ['action' => route('pod.detail'), 'keep' => ['node', 'bucket', 'color', 'q', 'sort', 'dir']])

    {{-- Breadcrumb pohon status: setiap level bisa diklik untuk naik --}}
    <nav class="crumbs">
        <a href="{{ route('pod.index', $f->globalQuery()) }}">Summary</a>
        @foreach ($f->node->path() as $n)
            @continue($n === PodNode::All && $f->node !== PodNode::All)
            <span class="sep">›</span>
            @if ($n === $f->node)
                <b>{{ $n->label() }}</b>
            @else
                <a href="{{ route('pod.detail', $f->with(['node' => $n])->toQuery()) }}">{{ $n->label() }}</a>
            @endif
        @endforeach
        @if ($f->scope)
            <a class="chip active" href="{{ $removeUrl(['scope' => null]) }}">{{ config("pod.scopes.{$f->scope}") }} ✕</a>
        @endif
        @if ($f->bucket)
            <a class="chip active" href="{{ $removeUrl(['bucket' => null]) }}">Umur {{ config("pod.aging_buckets.{$f->bucket}.label") }} hari ✕</a>
        @endif
        @if ($f->color)
            <a class="chip active" href="{{ $removeUrl(['color' => null]) }}"><span class="dot bg-{{ $f->color }}"></span> {{ ucfirst($f->color) }} ✕</a>
        @endif
    </nav>

    {{-- Turun ke anak node --}}
    @php $children = array_filter(PodNode::cases(), fn ($n) => $n->parent() === $f->node); @endphp
    @if ($children)
        <div class="legend">
            <span class="muted" style="align-self:center">Rinci ke:</span>
            @foreach ($children as $child)
                <a class="chip" href="{{ route('pod.detail', $f->with(['node' => $child])->toQuery()) }}">{{ $child->label() }}</a>
            @endforeach
        </div>
    @endif

    @if ($f->node->podRequired())
        <div class="legend">
            <span class="muted" style="align-self:center">{{ $f->node->isReturnedBranch() ? 'Lead time kembali' : 'Umur' }} per warna:</span>
            @foreach (config('pod.aging_colors') as $key => $c)
                <a @class(['chip', 'active' => $f->color === $key]) href="{{ $removeUrl(['color' => $f->color === $key ? null : $key]) }}">
                    <span class="dot bg-{{ $key }}"></span> {{ $c['label'] }} <b>{{ pod_num($colors[$key]) }}</b>
                </a>
            @endforeach
        </div>
    @endif

    <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px">
            <h2>{{ pod_num($rows->total()) }} AWB · {{ $f->node->label() }}</h2>
            <form method="get" action="{{ route('pod.detail') }}" style="display:flex;gap:6px">
                @foreach (array_diff_key($f->toQuery(), ['q' => 1]) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <input type="search" name="q" value="{{ $f->search }}" placeholder="Cari AWB…" maxlength="30"
                       style="font:inherit;padding:6px 8px;border:1px solid var(--line);border-radius:6px">
                <button class="btn" type="submit">Cari</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><a href="{{ $sortUrl('awb') }}">AWB{{ $arrow('awb') }}</a></th>
                    <th class="left"><a href="{{ $sortUrl('customer_name') }}">Customer{{ $arrow('customer_name') }}</a></th>
                    <th class="left"><a href="{{ $sortUrl('dest_branch_name') }}">Cabang / Perwakilan{{ $arrow('dest_branch_name') }}</a></th>
                    <th class="left"><a href="{{ $sortUrl('dest_city') }}">Tujuan{{ $arrow('dest_city') }}</a></th>
                    <th class="left"><a href="{{ $sortUrl('service_scope') }}">Scope{{ $arrow('service_scope') }}</a></th>
                    <th class="left"><a href="{{ $sortUrl('pod_status') }}">Status POD{{ $arrow('pod_status') }}</a></th>
                    <th><a href="{{ $sortUrl('shipment_date') }}">Tgl Kirim{{ $arrow('shipment_date') }}</a></th>
                    <th><a href="{{ $sortUrl('delivered_at') }}">Tgl Delivered{{ $arrow('delivered_at') }}</a></th>
                    <th>Tgl POD Kembali</th>
                    <th><a href="{{ $sortUrl('age_days') }}">Umur (hr){{ $arrow('age_days') }}</a></th>
                    <th class="left">Keterangan</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $r)
                    @php $status = $r->pod_status ? PodStatus::from($r->pod_status) : null; @endphp
                    <tr>
                        <td class="left"><b>{{ $r->awb }}</b></td>
                        <td class="left">{{ $r->customer_name }}</td>
                        <td class="left">{{ $r->dest_branch_name }} <span class="tag">{{ $r->dest_branch_type === 'PCP' ? 'PCP' : 'Perwakilan' }}</span></td>
                        <td class="left">{{ $r->dest_city }} <span class="muted">T{{ $r->dest_city_tier }}</span></td>
                        <td class="left">{{ config("pod.scopes.{$r->service_scope}") }}</td>
                        <td class="left">{{ $status?->label() ?? 'Tidak Balik' }}</td>
                        <td>{{ $date($r->shipment_date) }}</td>
                        <td>{{ $date($r->delivered_at) }}</td>
                        <td>{{ $date($r->pod_returned_at) }}</td>
                        <td>@if ($r->color)<span class="age bg-{{ $r->color }}">{{ $r->age_days }}</span>@else – @endif</td>
                        <td class="left muted">{{ $r->remarks }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted left">Tidak ada AWB untuk filter ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pager">
            <span class="muted">Halaman {{ $rows->currentPage() }} / {{ max(1, $rows->lastPage()) }}</span>
            @if ($rows->previousPageUrl()) <a class="btn" href="{{ $rows->previousPageUrl() }}">‹ Sebelumnya</a> @endif
            @if ($rows->nextPageUrl()) <a class="btn" href="{{ $rows->nextPageUrl() }}">Berikutnya ›</a> @endif
        </div>
    </section>
@endsection
