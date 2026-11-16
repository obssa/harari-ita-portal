<x-filament-panels::page>
    {{-- ADR-0017: one column per status; each card shows the reference, subject, priority, SLA state and assignee. --}}
    <div class="hinta-board">
        @foreach ($this->columns() as $status => $tickets)
            @php $state = \App\Domain\Support\Enums\TicketStatus::from($status); @endphp
            <section class="hinta-board-col" aria-labelledby="board-{{ $status }}">
                <h2 id="board-{{ $status }}" class="hinta-board-head">
                    <span>{{ $state->label() }}</span>
                    <span class="hinta-board-count">{{ $tickets->count() }}</span>
                </h2>
                <ul class="hinta-board-list">
                    @forelse ($tickets as $ticket)
                        @php $sla = $ticket->slaState(); @endphp
                        <li>
                            <a href="{{ \App\Filament\Resources\Tickets\TicketResource::getUrl('view', ['record' => $ticket]) }}" class="hinta-card hinta-card-{{ $sla->value }}">
                                <span class="hinta-card-ref">{{ $ticket->reference }}</span>
                                <span class="hinta-card-title">{{ \Illuminate\Support\Str::limit($ticket->subject, 90) }}</span>
                                <span class="hinta-card-meta">
                                    <span class="hinta-pill hinta-pill-{{ $ticket->priority->value }}">{{ $ticket->priority->label() }}</span>
                                    <span class="hinta-pill hinta-pill-sla-{{ $sla->value }}">{{ $sla->label() }}</span>
                                </span>
                                <span class="hinta-muted">{{ $ticket->assignee?->name ?? __('support.admin.unassigned') }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="hinta-empty">{{ __('support.admin.column_empty') }}</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
