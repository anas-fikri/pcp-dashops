<?php

if (! function_exists('pod_num')) {
    /** Format angka gaya Indonesia (1.234). */
    function pod_num(int|float|null $n, int $decimals = 0): string
    {
        return $n === null ? '–' : number_format($n, $decimals, ',', '.');
    }
}

if (! function_exists('pod_pct')) {
    function pod_pct(?float $n): string
    {
        return $n === null ? '–' : number_format($n, 1, ',', '.').'%';
    }
}

if (! function_exists('pod_color')) {
    /** Kunci warna severity (config pod.aging_colors) untuk umur tertentu. */
    function pod_color(?int $days): ?string
    {
        if ($days === null) {
            return null;
        }
        foreach (config('pod.aging_colors') as $key => $c) {
            if ($c['max'] === null || $days <= $c['max']) {
                return $key;
            }
        }

        return null;
    }
}
