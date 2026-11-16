@props(['post', 'shown', 'lead' => false, 'headingLevel' => 3])
@use('App\Domain\Translation\Support\Locales')
@php
    $url = route('news.show', ['locale' => $shown->locale, 'slug' => $shown->slug]);
    $other = $shown->locale !== app()->getLocale();
@endphp
{{-- ADR-0012: every story card has the same parts in the same places: picture (or the placeholder), labels, title,
     summary and a mono date; a hairline border that turns cyan on hover. --}}
<article lang="{{ $shown->locale }}" {{ $attributes->class([
    'reveal group relative flex h-full flex-col overflow-hidden rounded-card border border-line bg-surface transition duration-200 hover:-translate-y-0.5 hover:border-cyan-500 hover:shadow-xl hover:shadow-night-950/5',
]) }}>
    <div @class(['relative overflow-hidden bg-night-900', 'aspect-[16/10] shrink-0' => ! $lead, 'aspect-[16/9] shrink-0 lg:aspect-auto lg:min-h-[18rem] lg:flex-1' => $lead])>
        @if ($post->featuredImage)
            <x-picture :media="$post->featuredImage" preset="landscape" :sizes="$lead ? '(min-width: 64rem) 46rem, 100vw' : '(min-width: 64rem) 25rem, (min-width: 40rem) 50vw, 100vw'"
                       class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.03]" />
        @else
            <x-news-placeholder :type="$post->type" :lead="$lead" class="absolute inset-0" />
        @endif
        <div class="absolute start-3 top-3 flex flex-wrap gap-1.5">
            <x-ui.badge :tone="$post->type->tone()" class="shadow-sm">{{ $post->type->label() }}</x-ui.badge>
            @if ($other)
                <x-ui.badge class="shadow-sm">{{ __('news.in_language', ['language' => Locales::nativeName($shown->locale)]) }}</x-ui.badge>
            @endif
        </div>
    </div>
    <div @class(['flex flex-col gap-3', 'flex-1 p-5' => ! $lead, 'p-6 lg:p-7' => $lead])>
        @if ($shown->kicker)
            <p class="kicker">{{ $shown->kicker }}</p>
        @endif
        <h{{ $headingLevel }} @class([
            'font-display font-semibold leading-snug tracking-tight',
            'line-clamp-3 text-lg' => ! $lead,
            'line-clamp-3 text-2xl tracking-tight lg:text-[1.75rem]' => $lead,
        ])>
            <a href="{{ $url }}" class="underline-offset-4 after:absolute after:inset-0 group-hover:underline">{{ $shown->title }}</a>
        </h{{ $headingLevel }}>
        @if ($shown->excerpt)
            <p @class(['text-ink-muted', 'line-clamp-2 text-sm' => ! $lead, 'line-clamp-2 text-base' => $lead])>{{ $shown->excerpt }}</p>
        @endif
        <p class="mt-auto flex items-center gap-2 border-t border-line pt-3 font-mono text-xs text-ink-muted">
            <span aria-hidden="true" class="size-1.5 rounded-full bg-cyan-500"></span>
            <x-date :date="$shown->published_at" />
        </p>
    </div>
</article>
