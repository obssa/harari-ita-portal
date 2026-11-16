@use('App\Domain\Organisation\Queries\PublicDirectory')
@php $locale = app()->getLocale(); @endphp
<x-layouts.public :title="$past ? __('events.past') : __('events.title')" :description="__('events.description')">
    <x-page-hero :title="__('events.title')" :lead="__('events.description')" tone="plaster" :crumbs="[]" />
    <div class="mx-auto max-w-page px-4 pt-2 pb-12 sm:px-6">

        <nav aria-label="{{ __('events.views') }}" class="mt-6">
            <ul class="flex flex-wrap gap-2 text-sm">
                <li><x-ui.chip :href="route('events.index')" :active="! $past">{{ __('events.upcoming') }}</x-ui.chip></li>
                <li><x-ui.chip :href="route('events.index', ['past' => 1])" :active="$past">{{ __('events.past') }}</x-ui.chip></li>
                <li><x-ui.chip :href="route('events.index', ['month' => now(\App\Services\Calendar\EthiopianCalendar::timezone())->format('Y-m')])">{{ __('events.month_view') }}</x-ui.chip></li>
                <li><x-ui.chip :href="route('events.feed')">{{ __('events.subscribe') }}</x-ui.chip></li>
            </ul>
        </nav>

        @if ($events->isEmpty())
            <x-ui.empty-state class="mt-10">{{ $past ? __('directory.empty') : __('events.none_upcoming') }}</x-ui.empty-state>
        @else
            <ul class="mt-8 grid gap-4 lg:grid-cols-2">
                @foreach ($events as $event)
                    @php $shown = PublicDirectory::shown($event, $locale); @endphp
                    @continue($shown === null)
                    <li><x-event-row :event="$event" :translation="$shown" /></li>
                @endforeach
            </ul>

            <div class="mt-10">{{ $events->links() }}</div>
        @endif
    </div>
</x-layouts.public>
