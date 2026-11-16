@use('App\Domain\Innovation\Enums\LabelStatus')
@use('App\Domain\Innovation\Enums\StartupStage')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    $select = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
@endphp
<x-layouts.public :title="__('innovation.startups.title')" :description="__('innovation.startups.description')">
    {{-- ADR-0020: the directory opens on night with the search; the way to get listed sits beside the results. --}}
    <x-page-hero :title="__('innovation.startups.title')" :lead="__('innovation.startups.description')" :kicker="__('nav.innovation')" tone="night">
        <form method="get" action="{{ route('startups.index') }}" role="search" class="flex max-w-3xl flex-wrap gap-2">
            <label for="startup-search" class="sr-only">{{ __('innovation.startups.search') }}</label>
            <input id="startup-search" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="{{ __('innovation.startups.search') }}"
                   class="min-w-0 flex-1 rounded-control border border-night-line bg-night-950 px-5 py-3 font-display text-base text-night-ink placeholder:text-night-muted focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/40 focus:outline-none">
            @foreach (['sector' => $sector?->id, 'stage' => $stage?->value, 'label' => $label?->value] as $name => $value)
                @if ($value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <x-ui.button type="submit" variant="action" size="lg">{{ __('services.search_button') }}</x-ui.button>
        </form>
        <x-slot:actions>
            <x-ui.button :href="route('startups.apply')" variant="light">{{ __('innovation.startups.apply') }}</x-ui.button>
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        <form method="get" action="{{ route('startups.index') }}" class="mt-8 grid gap-4 rounded-card border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            @if ($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif
            <div>
                <label for="filter-sector" class="kicker">{{ __('innovation.field.sector') }}</label>
                <select id="filter-sector" name="sector" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach ($sectors as $option)
                        <option value="{{ $option->id }}" @selected($sector?->id === $option->id)>{{ $option->name($locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-stage" class="kicker">{{ __('innovation.field.stage') }}</label>
                <select id="filter-stage" name="stage" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach (StartupStage::cases() as $option)
                        <option value="{{ $option->value }}" @selected($stage === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-label" class="kicker">{{ __('innovation.field.label') }}</label>
                <select id="filter-label" name="label" class="{{ $select }}">
                    <option value="">{{ __('services.filter.any') }}</option>
                    @foreach ([LabelStatus::Verified, LabelStatus::SelfDeclared] as $option)
                        <option value="{{ $option->value }}" @selected($label === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit">{{ __('services.filter.apply') }}</x-ui.button>
                @if ($filtered)
                    <a href="{{ route('startups.index') }}" class="text-sm text-link underline-offset-4 hover:underline">{{ __('services.filter.clear') }}</a>
                @endif
            </div>
        </form>

        @if ($startups->isEmpty())
            <x-ui.empty-state class="mt-10" :title="$filtered ? null : __('innovation.startups.empty_title')">
                {{ $filtered ? __('innovation.startups.none_match') : __('innovation.startups.empty') }}
                @unless ($filtered)
                    <x-slot:action><x-ui.button :href="route('startups.apply')" variant="action" size="sm">{{ __('innovation.startups.apply') }}</x-ui.button></x-slot:action>
                @endunless
            </x-ui.empty-state>
        @else
            <p class="mt-8 font-mono text-xs tracking-wide text-ink-muted uppercase" role="status">{{ trans_choice('innovation.startups.count', $startups->total(), ['count' => $startups->total()]) }}</p>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($startups as $startup)
                    @php $shown = PublicDirectory::shown($startup, $locale); @endphp
                    @continue($shown === null)
                    <li><x-startup-card :startup="$startup" :translation="$shown" /></li>
                @endforeach
            </ul>
            <div class="mt-10">{{ $startups->links() }}</div>
        @endif

        {{-- The Ministry's address is to be confirmed with the Agency (docs/INPUTS.md) before it is linked. --}}
        <p class="mt-12 max-w-prose text-sm text-ink-muted">{{ __('innovation.startups.law') }}</p>
    </div>
</x-layouts.public>
