<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-briefcase">
        {{-- ADR-0037: what closes in the next seven days, soonest first, and the latest log entries. --}}
        @php $closing = $this->closing(); @endphp
        @if ($closing->isEmpty())
            <p class="hinta-empty">{{ __('dashboard.deadlines.empty') }}</p>
        @else
            <ul class="hinta-list">
                @foreach ($closing as $opportunity)
                    <li class="hinta-row">
                        <span style="min-width: 0">
                            <a class="hinta-link hinta-ellipsis" style="display: block" href="{{ \App\Filament\Resources\Opportunities\OpportunityResource::getUrl('edit', ['record' => $opportunity]) }}">{{ $opportunity->title() }}</a>
                            <span class="hinta-muted">{{ $opportunity->type->label() }}@if ($opportunity->reference) · {{ $opportunity->reference }}@endif</span>
                        </span>
                        <span class="hinta-num hinta-strong" style="white-space: nowrap">{{ \App\Filament\Support\CalendarDate::format(withTime: true)($opportunity->closes_at) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        @php $entries = $this->latestEntries(); @endphp
        @if ($entries->isNotEmpty())
            <h3 class="hinta-subhead">{{ __('dashboard.deadlines.latest_entries') }}</h3>
            <ul class="hinta-list">
                @foreach ($entries as $entry)
                    <li class="hinta-row">
                        <span class="hinta-ellipsis">{{ $entry->opportunity->title() }} · {{ $entry->kind->label() }} {{ $entry->number }}</span>
                        <span class="hinta-muted hinta-num" style="white-space: nowrap">{{ \App\Filament\Support\CalendarDate::format()($entry->published_at) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
