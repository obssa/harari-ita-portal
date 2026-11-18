@use('App\Domain\Organisation\Queries\PublicDirectory')
@use('App\Domain\Services\Enums\ServiceAudience')
@use('App\Domain\Services\Enums\ServiceChannel')
@php
    $locale = app()->getLocale();
    $select = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
@endphp
<x-layouts.public :title="__('services.title')" :description="__('services.description')">
    {{-- ADR-0016: the catalogue opens on night with the search; filters sit below as one plain GET form. --}}
    <x-page-hero :title="__('services.title')" :lead="__('services.description')" :kicker="__('services.kicker')" tone="night">
        <form method="get" action="{{ route('services.index') }}" role="search" class="flex max-w-3xl flex-wrap gap-2">
            <label for="service-search" class="sr-only">{{ __('services.search') }}</label>
            <input id="service-search" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="{{ __('services.search') }}"
                   class="min-w-0 flex-1 rounded-control border border-night-line bg-night-950 px-5 py-3 font-display text-base text-night-ink placeholder:text-night-muted focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/40 focus:outline-none">
            @foreach (['category' => $category?->id, 'audience' => $audience?->value, 'channel' => $channel?->value] as $name => $value)
                @if ($value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <x-ui.button type="submit" variant="action" size="lg">{{ __('services.search_button') }}</x-ui.button>
        </form>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        <form method="get" action="{{ route('services.index') }}" class="mt-8 grid gap-4 rounded-card border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            @if ($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif
            <div>
                <label for="filter-category" class="kicker">{{ __('services.filter.category') }}</label>
                <select id="filter-category" name="category" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->id }}" @selected($category?->id === $option->id)>{{ $option->name($locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-audience" class="kicker">{{ __('services.filter.audience') }}</label>
                <select id="filter-audience" name="audience" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach (ServiceAudience::cases() as $option)
                        <option value="{{ $option->value }}" @selected($audience === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-channel" class="kicker">{{ __('services.filter.channel') }}</label>
                <select id="filter-channel" name="channel" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach (ServiceChannel::cases() as $option)
                        <option value="{{ $option->value }}" @selected($channel === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit">{{ __('services.filter.apply') }}</x-ui.button>
                @if ($filtered)
                    <a href="{{ route('services.index') }}" class="text-sm text-link underline-offset-4 hover:underline">{{ __('services.filter.clear') }}</a>
                @endif
            </div>
        </form>

        @if ($services->isEmpty())
            <x-ui.empty-state class="mt-10">{{ $filtered ? __('services.none_match') : __('services.empty') }}</x-ui.empty-state>
        @else
            <p class="mt-8 font-mono text-xs tracking-wide text-ink-muted uppercase" role="status">{{ trans_choice('services.count', $services->total(), ['count' => $services->total()]) }}</p>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    @php $shown = PublicDirectory::shown($service, $locale); @endphp
                    @continue($shown === null)
                    <li><x-service-card :service="$service" :translation="$shown" /></li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $services->links() }}</div>
        @endif

        @if (Route::has('support.create'))
            <section aria-labelledby="services-help" class="relative isolate mt-16 overflow-hidden rounded-card border border-night-line bg-night-900 p-8 text-night-ink sm:p-10">
                <x-circuit :flow="true" class="absolute inset-y-0 end-0 -z-10 h-full w-1/2 text-cyan-500/25" />
                <h2 id="services-help" class="font-display text-2xl font-semibold tracking-tight">{{ __('services.request_help') }}</h2>
                <p class="mt-2 max-w-prose text-night-muted">{{ __('services.request_help_text') }}</p>
                <p class="mt-6"><x-ui.button :href="route('support.create')" variant="action">{{ __('nav.cta_support') }}</x-ui.button></p>
            </section>
        @endif
    </div>
</x-layouts.public>
