@php
    use App\Enums\PodNode;
    use App\Enums\PodStatus;
    $buckets = config('pod.aging_buckets');
    $keys = [...array_keys($buckets), 'total'];
    $link = fn (int $n, PodNode $node, $bucket) => $n
        ? '<a href="'.e($f->detailUrl(['node' => $node] + ($bucket === 'total' ? [] : ['bucket' => $bucket]))).'">'.pod_num($n).'</a>'
        : '0';
    $sum = fn (array $statuses) => array_combine($keys, array_map(
        fn ($k) => array_sum(array_map(fn (PodStatus $s) => $aging[$s->value][$k], $statuses)), $keys));
@endphp
<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Status</th>
            @foreach ($buckets as $b)
                <th class="bt-{{ pod_color($b['min']) }}">{{ $b['label'] }} hr</th>
            @endforeach
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($statuses as $s)
            @php $node = PodNode::forStatus($s); @endphp
            @if (! empty($subtotal) && $s === PodStatus::deliveredPending()[0])
                @php $row = $sum(PodStatus::deliveredPending()); @endphp
                <tr class="lvl2">
                    <td>Delivered (subtotal)</td>
                    @foreach ($keys as $k)
                        <td @class(['zero' => ! $row[$k]])>{!! $link($row[$k], PodNode::Delivered, $k) !!}</td>
                    @endforeach
                </tr>
            @endif
            <tr>
                <td style="padding-left: {{ ! empty($subtotal) && $s !== PodStatus::NotDelivered ? 28 : 10 }}px">{{ $s->label() }}</td>
                @foreach ($keys as $k)
                    <td @class(['zero' => ! $aging[$s->value][$k]])>{!! $link($aging[$s->value][$k], $node, $k) !!}</td>
                @endforeach
            </tr>
        @endforeach
        @php $row = $sum($statuses); @endphp
        <tr class="total">
            <td>Total {{ $parent->label() }}</td>
            @foreach ($keys as $k)
                <td>{!! $link($row[$k], $parent, $k) !!}</td>
            @endforeach
        </tr>
        </tbody>
    </table>
</div>
