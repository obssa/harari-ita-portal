@props(['items', 'title', 'id', 'note' => null])
@php
    // DESIGN.md §5: a bar chart that is its own data table: each row has the label, the number and a bar from zero.
    // Items: [['label' => string, 'value' => int|float, 'url' => ?string], ...].
    $max = max(1, ...array_map(fn (array $i): float => (float) $i['value'], $items ?: [['value' => 0]]));
    $number = fn (float $v): string => \Illuminate\Support\Number::format($v, maxPrecision: 1, locale: 'en');
@endphp
<figure {{ $attributes->class('rounded-card border border-line bg-surface p-5') }}>
    <table class="w-full text-start text-sm" aria-describedby="{{ $id }}-title">
        <caption id="{{ $id }}-title" class="mb-4 text-start font-semibold">{{ $title }}</caption>
        <thead class="sr-only">
            <tr><th scope="col">{{ __('charts.label') }}</th><th scope="col">{{ __('charts.value') }}</th></tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php $share = round((float) $item['value'] / $max * 100); @endphp
                <tr>
                    <th scope="row" class="w-2/5 py-1.5 pe-3 text-start align-middle font-normal">
                        @if (! empty($item['url']))
                            <a href="{{ $item['url'] }}" class="text-link underline-offset-4 hover:underline">{{ $item['label'] }}</a>
                        @else
                            {{ $item['label'] }}
                        @endif
                    </th>
                    <td class="py-1.5 align-middle">
                        <span class="flex items-center gap-3">
                            <span aria-hidden="true" class="block h-3 flex-1 overflow-hidden rounded-sm bg-surface-muted ring-1 ring-line ring-inset">
                                <span class="block h-full rounded-sm bg-cyan-600 dark:bg-cyan-400" style="width: {{ $share }}%"></span>
                            </span>
                            <span class="w-12 text-end font-mono text-xs tabular-nums" dir="ltr">{{ $number((float) $item['value']) }}</span>
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($note)
        <p class="mt-3 font-mono text-xs text-ink-muted">{{ $note }}</p>
    @endif
</figure>
