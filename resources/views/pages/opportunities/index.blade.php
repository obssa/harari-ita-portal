@use('App\Domain\Opportunities\Enums\OpportunityType')
@use('App\Domain\Organisation\Queries\PublicDirectory')
@php
    $locale = app()->getLocale();
    $title = $type ? $type->plural() : __('opportunities.title');
@endphp
<x-layouts.public :title="$title" :description="__('opportunities.description')">
    {{-- ADR-0032: what is open now, by nearest deadline, then the archive. Filters by type keep the archive in step. --}}
    <x-page-hero :title="$title" :lead="__('opportunities.description')" :kicker="__('opportunities.kicker')" tone="plaster"
                 :crumbs="$type ? [[__('opportunities.title'), route('opportunities.index')]] : []">
        <x-slot:actions>
            <x-ui.button :href="route('opportunities.feed')" variant="secondary" size="sm">{{ __('opportunities.feed') }}</x-ui.button>
            <x-ui.button :href="route('newsletter.create')" variant="secondary" size="sm">{{ __('opportunities.subscribe') }}</x-ui.button>
        </x-slot:actions>
        <nav aria-label="{{ __('opportunities.filter_by_type') }}">
            <ul class="flex flex-wrap gap-2">
                <li><x-ui.chip :href="route('opportunities.index')" :active="! $type">{{ __('opportunities.all') }}</x-ui.chip></li>
                @foreach (OpportunityType::cases() as $option)
                    <li>
                        <x-ui.chip :href="route('opportunities.index', ['type' => $option->value])" :active="$type === $option">
                            {{ $option->plural() }}
                            @if (($counts[$option->value] ?? 0) > 0)
                                <span class="ms-1.5 font-mono text-xs opacity-80">{{ $counts[$option->value] }}</span>
                            @endif
                        </x-ui.chip>
                    </li>
                @endforeach
            </ul>
        </nav>
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-2 pb-16 sm:px-6">
        <section aria-labelledby="opportunities-current" class="mt-10">
            <p class="kicker">{{ __('opportunities.current_kicker') }}</p>
            <h2 id="opportunities-current" class="heading-2 mt-1">{{ __('opportunities.current') }}</h2>
            @if ($current->isEmpty())
                <x-ui.empty-state class="mt-6">{{ __('opportunities.none_open') }}</x-ui.empty-state>
            @else
                <ul class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($current as $opportunity)
                        @php $shown = PublicDirectory::shown($opportunity, $locale); @endphp
                        @continue($shown === null)
                        <li><x-opportunity-card :opportunity="$opportunity" :translation="$shown" /></li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="opportunities-archive" class="mt-16">
            <p class="kicker">{{ __('opportunities.archive_kicker') }}</p>
            <h2 id="opportunities-archive" class="heading-2 mt-1">{{ __('opportunities.archive') }}</h2>
            <p class="mt-2 max-w-prose text-ink-muted">{{ __('opportunities.archive_help') }}</p>
            @if ($past->isEmpty())
                <x-ui.empty-state class="mt-6">{{ __('opportunities.none_archived') }}</x-ui.empty-state>
            @else
                <ul class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($past as $opportunity)
                        @php $shown = PublicDirectory::shown($opportunity, $locale); @endphp
                        @continue($shown === null)
                        <li><x-opportunity-card :opportunity="$opportunity" :translation="$shown" /></li>
                    @endforeach
                </ul>
                <div class="mt-8">{{ $past->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.public>
