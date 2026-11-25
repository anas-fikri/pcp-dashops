<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Performance POD') · PCP DashOps</title>
    <style>
        :root {
            --bg: #f4f6f9; --card: #fff; --ink: #1f2328; --muted: #5f6b7a; --line: #e3e8ef;
            --accent: #1f3a5f; --accent-soft: #e8eef6; --link: #1d5fbf;
            --c-hitam: #1f2328; --c-oranye: #d9730d; --c-merah: #c62828; --c-ungu: #7b1fa2;
            --ok: #1b7f4a;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font: 14px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        a { color: var(--link); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header.top { background: var(--accent); color: #fff; padding: 12px 24px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        header.top h1 { font-size: 18px; margin: 0; font-weight: 600; }
        header.top .meta { opacity: .8; font-size: 13px; }
        header.top .spacer { flex: 1; }
        main { padding: 20px 24px 48px; max-width: 1440px; margin: 0 auto; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 6px; border: 1px solid var(--line); background: #fff; color: var(--ink); font: inherit; cursor: pointer; white-space: nowrap; }
        .btn:hover { text-decoration: none; background: #f7f9fc; }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn.light { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.35); color: #fff; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 16px 18px; }
        .card h2 { font-size: 15px; margin: 0 0 4px; }
        .card .sub { color: var(--muted); font-size: 12.5px; margin: 0 0 12px; }
        .grid { display: grid; gap: 16px; }
        .grid.two { grid-template-columns: repeat(auto-fit, minmax(min(520px, 100%), 1fr)); }
        .grid > * { min-width: 0; }
        .filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; margin-bottom: 16px; }
        .filters label { display: flex; flex-direction: column; font-size: 12px; color: var(--muted); gap: 3px; }
        .filters input, .filters select { font: inherit; padding: 6px 8px; border: 1px solid var(--line); border-radius: 6px; background: #fff; min-width: 130px; }
        .errors { background: #fdecea; color: #8a1c1c; border: 1px solid #f5c2c0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
        .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .kpi { display: block; color: inherit; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 14px 16px; }
        .kpi:hover { text-decoration: none; border-color: var(--accent); }
        .kpi .label { font-size: 12.5px; color: var(--muted); }
        .kpi .value { font-size: 26px; font-weight: 650; margin: 2px 0; font-variant-numeric: tabular-nums; }
        .kpi .hint { font-size: 12px; color: var(--muted); }
        .kpi.alert { border-left: 4px solid var(--c-ungu); }
        table { border-collapse: collapse; width: 100%; font-variant-numeric: tabular-nums; }
        th, td { padding: 7px 10px; border-bottom: 1px solid var(--line); text-align: right; white-space: nowrap; }
        th { font-size: 12px; color: var(--muted); font-weight: 600; background: #f8fafc; position: sticky; top: 0; }
        th:first-child, td:first-child, .left { text-align: left; }
        tr.total td { font-weight: 650; background: #f8fafc; }
        tr.lvl0 td:first-child, tr.lvl1 td:first-child, tr.lvl2 td:first-child { font-weight: 600; }
        tr.lvl2 td { background: #fbfcfe; }
        td.zero, td.zero a { color: #b4bcc8; }
        .table-wrap { overflow-x: auto; }
        .legend { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px; }
        .chip { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; background: #fff; border: 1px solid var(--line); border-radius: 999px; color: var(--ink); font-size: 13px; }
        .chip:hover { text-decoration: none; border-color: var(--accent); }
        .chip.active { background: var(--accent-soft); border-color: var(--accent); }
        .chip b { font-variant-numeric: tabular-nums; }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .c-hitam { color: var(--c-hitam); } .c-oranye { color: var(--c-oranye); } .c-merah { color: var(--c-merah); } .c-ungu { color: var(--c-ungu); }
        .bg-hitam { background: var(--c-hitam); } .bg-oranye { background: var(--c-oranye); } .bg-merah { background: var(--c-merah); } .bg-ungu { background: var(--c-ungu); }
        .bt-hitam { box-shadow: inset 0 3px 0 var(--c-hitam); } .bt-oranye { box-shadow: inset 0 3px 0 var(--c-oranye); }
        .bt-merah { box-shadow: inset 0 3px 0 var(--c-merah); } .bt-ungu { box-shadow: inset 0 3px 0 var(--c-ungu); }
        .age { display: inline-block; min-width: 38px; padding: 2px 8px; border-radius: 4px; color: #fff; font-weight: 600; text-align: center; }
        .tag { display: inline-block; padding: 1px 7px; border-radius: 4px; background: var(--accent-soft); color: var(--accent); font-size: 12px; }
        .crumbs { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 12px; font-size: 13px; }
        .crumbs span.sep { color: var(--muted); }
        .muted { color: var(--muted); }
        .pager { display: flex; gap: 10px; align-items: center; justify-content: flex-end; margin-top: 12px; }
        .bar { height: 8px; border-radius: 4px; background: var(--line); overflow: hidden; min-width: 80px; }
        .bar > span { display: block; height: 100%; background: var(--ok); }
        footer { color: var(--muted); font-size: 12px; text-align: center; padding: 16px; }
        @media (max-width: 640px) {
            main { padding: 16px; } header.top { padding: 12px 16px; }
            .grid.two { grid-template-columns: 1fr; }
            .kpis { grid-template-columns: 1fr 1fr; } .kpi .value { font-size: 22px; }
            .filters label, .filters input, .filters select { width: 100%; }
        }
    </style>
</head>
<body>
<header class="top">
    <h1>Dashboard Performance POD</h1>
    <span class="meta">Posisi per {{ $f->asOf->translatedFormat('d M Y') }} · periode kirim {{ $f->dateFrom->format('d/m/Y') }} – {{ $f->dateTo->format('d/m/Y') }}</span>
    <span class="spacer"></span>
    @yield('actions')
</header>
<main>
    @if ($errors->any())
        <div class="errors">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif
    @yield('content')
</main>
<footer>PCP DashOps · angka dapat diklik untuk melihat detail AWB</footer>
</body>
</html>
