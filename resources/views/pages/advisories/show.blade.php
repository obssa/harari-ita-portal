@use('App\Domain\Content\Queries\PublicPosts')
@use('App\Domain\Translation\Support\Locales')
@php
    $locale = app()->getLocale();
    $severity = $post->severity;
    $tone = match ($severity?->value) { 'warning' => 'danger', 'advisory' => 'warning', default => 'info' };
@endphp
<x-layouts.public
    :title="$translation->seo_title ?: $translation->title"
    :description="$translation->seo_description ?: $translation->excerpt"
    :alternates="$alternates"
    :image="$image"
    type="article"
    :json-ld="$jsonLd"
>
    {{-- ADR-0034: an advisory: its severity in words and icon, the date, the affected systems, the steps to take in
         order, the details, and where to report an incident. --}}
    <x-page-hero :title="$translation->title" :lead="$translation->excerpt" :kicker="__('advisories.kicker')" tone="plaster"
                 :crumbs="[[__('advisories.title'), route('advisories.index')]]" :heading-lang="$translation->locale">
        <p class="flex flex-wrap items-center gap-3 text-sm text-ink-muted">
            @if ($severity)
                <x-ui.badge :tone="$severity->tone()">{{ $severity->label() }}</x-ui.badge>
            @endif
            <span>{{ __('advisories.published') }} <x-date :date="$translation->published_at" /></span>
            @if ($translation->updated_at && $translation->published_at && $translation->updated_at->gt($translation->published_at->addHour()))
                <span>· {{ __('advisories.updated') }} <x-date :date="$translation->updated_at" /></span>
            @endif
        </p>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-[1fr_20rem]">
            <article class="min-w-0 space-y-10" lang="{{ $translation->locale }}">
                @if ($severity)
                    <x-ui.alert :tone="$tone" :title="$severity->label()">
                        <p>{{ __('advisories.severity.'.$severity->value.'_help') }}</p>
                    </x-ui.alert>
                @endif

                @if ($translation->affectedList() !== [])
                    <section aria-labelledby="advisory-affected">
                        <h2 id="advisory-affected" class="heading-2">{{ __('advisories.affected') }}</h2>
                        <ul class="mt-5 flex flex-wrap gap-2">
                            @foreach ($translation->affectedList() as $system)
                                <li class="rounded-control border border-line bg-surface px-3.5 py-2 font-medium">{{ $system }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($translation->actionList() !== [])
                    <section aria-labelledby="advisory-actions">
                        <h2 id="advisory-actions" class="heading-2">{{ __('advisories.actions') }}</h2>
                        <ol class="relative mt-6 space-y-5 before:absolute before:inset-y-2 before:start-4 before:w-px before:bg-line">
                            @foreach ($translation->actionList() as $step)
                                <li class="relative flex gap-4">
                                    <span aria-hidden="true" class="relative grid size-8 shrink-0 place-items-center rounded-control bg-night-900 font-mono text-sm text-cyan-300 ring-4 ring-canvas">{{ $loop->iteration }}</span>
                                    <p class="pt-1 text-lg leading-relaxed">{{ $step }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if (trim($body) !== '')
                    <section aria-labelledby="advisory-details">
                        <h2 id="advisory-details" class="heading-2">{{ __('advisories.details') }}</h2>
                        <div class="prose-content mt-5 max-w-prose">{!! $body !!}</div>
                    </section>
                @endif

                @php $others = array_filter($alternates, fn ($url, $l) => $l !== $locale && $post->translation($l, fallback: false)?->isLive(), ARRAY_FILTER_USE_BOTH); @endphp
                @if ($others !== [])
                    <p class="text-sm">
                        {{ __('news.also_available') }}
                        @foreach ($others as $l => $url)
                            <a href="{{ $url }}" lang="{{ $l }}" hreflang="{{ $l }}" class="ms-1 font-medium text-link underline-offset-4 hover:underline">{{ Locales::nativeName($l) }}</a>
                        @endforeach
                    </p>
                @endif
            </article>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <x-advisory-report />
                @if ($recent->isNotEmpty())
                    <section aria-labelledby="advisory-recent" class="rounded-card border border-line bg-surface p-5">
                        <h2 id="advisory-recent" class="font-semibold">{{ __('advisories.recent') }}</h2>
                        <ul class="mt-3 space-y-3 text-sm">
                            @foreach ($recent as $item)
                                @php $shown = PublicPosts::shown($item, $locale); @endphp
                                @continue($shown === null)
                                <li lang="{{ $shown->locale }}">
                                    @if ($item->severity)
                                        <x-ui.badge :tone="$item->severity->tone()">{{ $item->severity->label() }}</x-ui.badge>
                                    @endif
                                    <a href="{{ $item->url($shown) }}" class="mt-1 block font-semibold underline-offset-4 hover:underline">{{ $shown->title }}</a>
                                    <span class="font-mono text-xs text-ink-muted"><x-date :date="$shown->published_at" /></span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('advisories.index') }}" class="mt-4 inline-block text-sm font-semibold text-link underline-offset-4 hover:underline">{{ __('advisories.all_link') }}</a>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.public>
