<x-filament-widgets::widget>
    <x-filament::section :heading="$this::title()" icon="heroicon-o-lifebuoy">
        @php $sla = $this->sla(); @endphp
        {{-- ADR-0017: open tickets by SLA state, each linking to the filtered list. --}}
        <dl class="hinta-list">
            @foreach (['on_time', 'due_soon', 'breached'] as $state)
                @php $enum = \App\Domain\Support\Enums\SlaState::from($state); @endphp
                <div class="hinta-row">
                    <dt><x-filament::badge :color="$enum->color()">{{ $enum->label() }}</x-filament::badge></dt>
                    <dd class="hinta-num hinta-strong">{{ $sla[$state] ?? 0 }}</dd>
                </div>
            @endforeach
        </dl>
        <p style="margin-top: .75rem">
            <a class="hinta-link" href="{{ \App\Filament\Resources\Tickets\TicketResource::getUrl('board') }}">{{ __('support.admin.board') }}</a>
        </p>
        @php $incidents = $this->incidents(); @endphp
        @if ($incidents->isNotEmpty())
            <h3 class="hinta-subhead">{{ __('status.active') }}</h3>
            <ul class="hinta-list">
                @foreach ($incidents as $incident)
                    <li class="hinta-row">
                        <a class="hinta-link hinta-ellipsis" href="{{ \App\Filament\Resources\Incidents\IncidentResource::getUrl('edit', ['record' => $incident]) }}">{{ $incident->title() }}</a>
                        <x-filament::badge :color="$incident->impact->color()">{{ $incident->impact->label() }}</x-filament::badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
