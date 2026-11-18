@props(['dataset', 'translation', 'headingLevel' => 2])
@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    $office = $dataset->office !== null ? PublicDirectory::shown($dataset->office, $locale) : null;
    $formats = $dataset->distributions->map(fn ($d) => $d->format)->unique(fn ($f) => $f->value)->values();
@endphp
{{-- ADR-0025: a dataset as a card: the publisher in mono, the title, the summary, its format chips, and at the foot the
     last update (with an overdue note) and the licence. --}}
<article {{ $attributes->class('glow-card group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-6 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5') }} lang="{{ $translation->locale }}">
    <div class="flex items-start justify-between gap-3">
        <p class="kicker">{{ $office?->getAttribute('name') ?? __('opendata.title') }}</p>
        @if ($dataset->isOverdue())
            <x-ui.badge tone="warning">{{ __('opendata.overdue') }}</x-ui.badge>
        @endif
    </div>
    <div>
        <h{{ $headingLevel }} class="font-display text-xl leading-snug font-semibold tracking-tight">
            <a href="{{ route('datasets.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $translation->title }}</a>
        </h{{ $headingLevel }}>
        @if (filled($translation->summary))
            <p class="mt-2 line-clamp-3 text-ink-muted">{{ $translation->summary }}</p>
        @endif
    </div>
    @if ($formats->isNotEmpty())
        <ul class="flex flex-wrap gap-1.5" aria-label="{{ __('opendata.formats') }}">
            @foreach ($formats as $format)
                <li><x-ui.badge tone="cyan">{{ $format->code() }}</x-ui.badge></li>
            @endforeach
        </ul>
    @endif
    <dl class="mt-auto grid gap-1 border-t border-line pt-4 font-mono text-xs text-ink-muted">
        @if ($dataset->data_updated_on)
            <div class="flex gap-2"><dt>{{ __('opendata.field.data_updated_on') }}:</dt><dd class="text-ink"><x-date :date="$dataset->data_updated_on" /></dd></div>
        @endif
        <div class="flex gap-2"><dt>{{ __('opendata.field.licence') }}:</dt><dd class="text-ink">{{ $dataset->licence->spdx() }}</dd></div>
    </dl>
</article>
