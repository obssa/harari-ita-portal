@php
    $locale = app()->getLocale();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
@endphp
{{-- ADR-0018: one incident: impact, title, affected systems, and its updates newest first. --}}
<article class="rounded-card border border-line bg-surface p-5" aria-labelledby="incident-{{ $incident->id }}">
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.status-pill :state="$incident->resolved_at ? 'ok' : $incident->impact->pill()">{{ $incident->resolved_at ? __('status.stage.resolved') : $incident->impact->label() }}</x-ui.status-pill>
        <span class="font-mono text-xs tracking-wide text-ink-muted uppercase">{{ $incident->kind->label() }}</span>
    </div>
    <h3 id="incident-{{ $incident->id }}" class="mt-3 font-display text-xl font-semibold tracking-tight">{{ $incident->title($locale) }}</h3>
    <p class="mt-1 font-mono text-xs text-ink-muted">
        {{ __('status.affects') }}: {{ $incident->components->map(fn ($c) => $c->name($locale))->join(', ') }}
        · {{ $incident->isUpcoming() ? __('status.starts') : __('status.started') }} {{ $calendar->format($incident->started_at, withTime: true) }}
        @if ($incident->resolved_at)
            · {{ __('status.resolved_at') }} {{ $calendar->format($incident->resolved_at, withTime: true) }}
        @endif
    </p>
    @if ($incident->updates->isNotEmpty())
        <ol class="mt-4 space-y-3 border-s-2 border-line ps-4">
            @foreach ($incident->updates as $update)
                <li>
                    <p class="font-mono text-xs text-ink-muted"><span class="font-semibold text-ink uppercase">{{ $update->stage->label() }}</span> · {{ $calendar->format($update->created_at, withTime: true) }}</p>
                    <p class="mt-1 whitespace-pre-line">{{ $update->body($locale) }}</p>
                </li>
            @endforeach
        </ol>
    @endif
</article>
