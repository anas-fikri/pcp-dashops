{{-- Filter global. $action = route tujuan; parameter drill-down dipertahankan via hidden input. --}}
<form method="get" action="{{ $action }}" class="filters card">
    <label>Tgl kirim dari
        <input type="date" name="date_from" value="{{ $f->dateFrom->toDateString() }}">
    </label>
    <label>s/d
        <input type="date" name="date_to" value="{{ $f->dateTo->toDateString() }}">
    </label>
    <label>Customer
        <select name="customer">
            <option value="">Semua</option>
            @foreach ($options['customers'] as $c)
                <option value="{{ $c->code }}" @selected($f->customer === $c->code)>{{ $c->name }}</option>
            @endforeach
        </select>
    </label>
    <label>PCP / Perwakilan
        <select name="branch_type">
            <option value="">Semua</option>
            @foreach (config('pod.branch_types') as $k => $v)
                <option value="{{ $k }}" @selected($f->branchType === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>
    <label>Cabang
        <select name="branch">
            <option value="">Semua</option>
            @foreach ($options['branches'] as $b)
                <option value="{{ $b->code }}" @selected($f->branch === $b->code)>{{ $b->name }}</option>
            @endforeach
        </select>
    </label>
    <label>Tier kota
        <select name="tier">
            <option value="">Semua</option>
            @foreach (config('pod.city_tiers') as $k => $v)
                <option value="{{ $k }}" @selected($f->tier === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>
    <label>Scope
        <select name="scope">
            <option value="">Semua</option>
            @foreach (config('pod.scopes') as $k => $v)
                <option value="{{ $k }}" @selected($f->scope === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </label>
    @foreach (array_intersect_key($f->toQuery(), array_flip($keep ?? [])) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    @if (! $f->asOf->isToday())
        <input type="hidden" name="as_of" value="{{ $f->asOf->toDateString() }}">
    @endif
    <button class="btn primary" type="submit">Terapkan</button>
    <a class="btn" href="{{ $action }}">Reset</a>
</form>
