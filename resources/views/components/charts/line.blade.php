@props(['points', 'title', 'unit' => null, 'target' => null, 'id'])
@php
    // DESIGN.md §5: a server-drawn SVG line with its numbers in a table; no chart library. Points are
    // [['label' => string, 'value' => float], ...] in time order. A target, when given, is a dashed line.
    $points = array_values($points);
    $values = array_map(fn (array $p): float => (float) $p['value'], $points);
    $all = $target !== null ? [...$values, (float) $target] : $values;
    $min = min(0.0, ...$all);
    $max = max(...$all);
    if ($max == $min) {
        $max = $min + 1;
    }
    [$w, $h, $left, $right, $top, $bottom] = [320, 120, 8, 8, 10, 18];
    $x = fn (int $i): float => count($points) > 1 ? $left + $i * ($w - $left - $right) / (count($points) - 1) : $w / 2;
    $y = fn (float $v): float => $top + ($max - $v) * ($h - $top - $bottom) / ($max - $min);
    $line = implode(' ', array_map(fn (int $i): string => round($x($i), 1).','.round($y($values[$i]), 1), array_keys($values)));
    $number = fn (float $v): string => \Illuminate\Support\Number::format($v, maxPrecision: 2, locale: 'en');
@endphp
<figure {{ $attributes->class('text-sm') }}>
    <figcaption class="sr-only">{{ $title }}</figcaption>
    <svg viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-labelledby="{{ $id }}-title" class="h-auto w-full overflow-visible">
        <title id="{{ $id }}-title">{{ $title }}</title>
        <line x1="{{ $left }}" x2="{{ $w - $right }}" y1="{{ round($y($min), 1) }}" y2="{{ round($y($min), 1) }}" class="stroke-line" stroke-width="1" />
        @if ($target !== null)
            <line x1="{{ $left }}" x2="{{ $w - $right }}" y1="{{ round($y((float) $target), 1) }}" y2="{{ round($y((float) $target), 1) }}" class="stroke-saffron-500" stroke-width="1.5" stroke-dasharray="4 4" />
        @endif
        <polyline points="{{ $line }}" fill="none" class="stroke-cyan-600 dark:stroke-cyan-400" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        @foreach ($values as $i => $value)
            <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($value), 1) }}" r="3.5" class="fill-surface stroke-cyan-600 dark:stroke-cyan-400" stroke-width="2" />
        @endforeach
        <text x="{{ $left }}" y="{{ $h - 3 }}" class="fill-ink-muted font-mono text-[9px]">{{ $points[0]['label'] }}</text>
        @if (count($points) > 1)
            <text x="{{ $w - $right }}" y="{{ $h - 3 }}" text-anchor="end" class="fill-ink-muted font-mono text-[9px]">{{ $points[count($points) - 1]['label'] }}</text>
        @endif
    </svg>
    <details class="mt-2">
        <summary class="cursor-pointer text-xs text-link underline-offset-4 hover:underline">{{ __('charts.show_numbers') }}</summary>
        <table class="mt-2 w-full text-start text-xs">
            <caption class="sr-only">{{ $title }}</caption>
            <thead>
                <tr class="border-b border-line text-ink-muted">
                    <th scope="col" class="py-1 text-start font-medium">{{ __('charts.date') }}</th>
                    <th scope="col" class="py-1 text-end font-medium">{{ $unit ? __('charts.value_in', ['unit' => $unit]) : __('charts.value') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($points as $point)
                    <tr class="border-b border-line/60">
                        <td class="py-1">{{ $point['label'] }}</td>
                        <td class="py-1 text-end tabular-nums" dir="ltr">{{ $number((float) $point['value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
