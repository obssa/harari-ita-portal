@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
{{-- ADR-0016: the top services on the homepage, with the ways into the service desk beside them. --}}
<section aria-labelledby="home-services" class="mx-auto max-w-page px-4 py-20 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">{{ __('services.kicker') }}</p>
            <h2 id="home-services" class="heading-2 mt-2">{{ $section->heading() }}</h2>
        </div>
        <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-link">
            {{ __('services.all') }}
            <svg aria-hidden="true" class="size-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
    <div class="mt-10 grid gap-4 lg:grid-cols-[1fr_18rem]">
        <ul class="grid gap-4 sm:grid-cols-2">
            @foreach ($data as $service)
                @php $shown = PublicDirectory::shown($service, $locale); @endphp
                @continue($shown === null)
                <li><x-service-card :service="$service" :translation="$shown" :heading-level="3" /></li>
            @endforeach
        </ul>
        <nav aria-label="{{ __('support.public.kicker') }}" class="relative isolate flex flex-col gap-2 overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
            <x-circuit class="absolute inset-x-0 bottom-0 -z-10 h-28 w-full text-cyan-500/25" />
            <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('support.public.kicker') }}</p>
            @foreach (array_filter([
                Route::has('support.create') ? [route('support.create'), __('nav.support_new'), __('nav.support_new_hint')] : null,
                Route::has('support.lookup') ? [route('support.lookup'), __('nav.support_track'), __('nav.support_track_hint')] : null,
                Route::has('status') ? [route('status'), __('nav.status'), __('nav.status_hint')] : null,
            ]) as [$url, $label, $hint])
                <a href="{{ $url }}" class="group block rounded-control border border-night-line px-4 py-3 transition-colors hover:border-cyan-400 hover:bg-white/5">
                    <span class="block font-display font-semibold tracking-tight">{{ $label }}</span>
                    <span class="mt-0.5 block text-sm text-night-muted">{{ $hint }}</span>
                </a>
            @endforeach
            <span class="h-16" aria-hidden="true"></span>
        </nav>
    </div>
</section>
