@props(['kpi'])
@php
    // ADR-0024: the latest reading against the target, how far it has come from the baseline, and the readings over time.
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $number = fn (float|string|null $v): string => $v === null ? '—' : \Illuminate\Support\Number::format((float) $v, maxPrecision: 2, locale: 'en');
    $unit = $kpi->unit();
    $latest = $kpi->readings->last();
    $progress = $latest !== null ? $kpi->progress((float) $latest->value) : null;
    $met = $latest !== null && ($kpi->higher_is_better ? (float) $latest->value >= (float) $kpi->target : (float) $latest->value <= (float) $kpi->target);
    $points = $kpi->readings->map(fn ($r): array => ['label' => $calendar->format($r->as_of), 'short' => $r->as_of->format('Y-m'), 'value' => (float) $r->value])->all();
@endphp
<article {{ $attributes->class('flex flex-col rounded-card border border-line bg-surface p-5') }}>
    <h3 class="font-semibold leading-snug">{{ $kpi->name() }}</h3>
    @if ($latest)
        <p class="mt-3 font-display text-3xl font-bold tabular-nums text-ink" dir="ltr">{{ $number($latest->value) }}@if (filled($unit))<span class="ms-1 text-base font-semibold text-ink-muted">{{ $unit }}</span>@endif</p>
        <p class="mt-1 font-mono text-xs text-ink-muted">{{ __('projects.kpi_measured') }} <x-date :date="$latest->as_of" /></p>
    @else
        <p class="mt-3 text-sm text-ink-muted">{{ __('projects.kpi_no_reading') }}</p>
    @endif

    <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
        <div>
            <dt class="kicker">{{ __('projects.kpi_target') }}</dt>
            <dd class="mt-0.5 tabular-nums" dir="ltr">{{ $number($kpi->target) }} {{ $unit }}</dd>
        </div>
        @if ($kpi->baseline !== null)
            <div>
                <dt class="kicker">{{ __('projects.kpi_baseline') }}</dt>
                <dd class="mt-0.5 tabular-nums" dir="ltr">{{ $number($kpi->baseline) }} {{ $unit }}</dd>
            </div>
        @endif
    </dl>

    @if ($progress !== null)
        <div class="mt-4">
            <x-progress-bar :value="$progress" :label="__('projects.kpi_towards', ['name' => $kpi->name()])" :caption="__('projects.kpi_way')" />
            @if ($met)
                <p class="mt-2"><x-ui.badge tone="success">{{ __('projects.kpi_met') }}</x-ui.badge></p>
            @endif
        </div>
    @endif

    @if (count($points) > 1)
        <x-charts.line :points="$points" :title="__('projects.kpi_chart', ['name' => $kpi->name()])" :unit="$unit ?: null" :target="(float) $kpi->target" :id="'kpi-'.$kpi->id" class="mt-5" />
    @endif
</article>
