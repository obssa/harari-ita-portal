@props(['service', 'translation', 'headingLevel' => 2])
@use('App\Domain\Organisation\Queries\PublicDirectory')
@use('App\Domain\Services\Enums\ServiceChannel')
@php
    $locale = app()->getLocale();
    $category = $service->category?->name($locale) ?: null;
    $office = $service->office !== null ? PublicDirectory::shown($service->office, $locale) : null;
@endphp
{{-- ADR-0016: a service as a card: the category in mono, the title, the summary, how it can be used (channel
     badges) and, at the foot, the typical time and the responsible office. --}}
<article {{ $attributes->class('group relative flex h-full flex-col gap-4 rounded-card border border-line bg-surface p-6 transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-lg hover:shadow-night-950/5') }} lang="{{ $translation->locale }}">
    <div class="flex items-start justify-between gap-3">
        <p class="kicker">{{ $category ?? __('services.title') }}</p>
    </div>
    <div>
        <h{{ $headingLevel }} class="font-display text-xl leading-snug font-semibold tracking-tight">
            <a href="{{ route('services.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="after:absolute after:inset-0 group-hover:underline underline-offset-4">{{ $translation->title }}</a>
        </h{{ $headingLevel }}>
        @if (filled($translation->summary))
            <p class="mt-2 line-clamp-3 text-ink-muted">{{ $translation->summary }}</p>
        @endif
    </div>
    <ul class="flex flex-wrap gap-1.5" aria-label="{{ __('services.channels') }}">
        @foreach ($service->channelList() as $channel)
            <li><x-ui.badge>{{ $channel->label() }}</x-ui.badge></li>
        @endforeach
    </ul>
    @if (filled($translation->duration) || $office)
        <dl class="mt-auto grid gap-1 border-t border-line pt-4 font-mono text-xs text-ink-muted">
            @if (filled($translation->duration))
                <div class="flex gap-2"><dt>{{ __('services.duration') }}:</dt><dd class="text-ink">{{ $translation->duration }}</dd></div>
            @endif
            @if ($office)
                <div class="flex gap-2"><dt>{{ __('services.office') }}:</dt><dd class="truncate text-ink">{{ $office->getAttribute('name') }}</dd></div>
            @endif
        </dl>
    @endif
</article>
