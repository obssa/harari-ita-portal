@php
    /** @var \App\Domain\Status\Models\Incident|null $incident */
    $incident = $this->getRecord();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
@endphp
{{-- ADR-0018: the public updates so far, newest first. --}}
<div class="hinta-thread">
    @forelse ($incident?->updates ?? [] as $update)
        <article class="hinta-msg">
            <header class="hinta-msg-head">
                <span class="hinta-msg-tag">{{ $update->stage->label() }}</span>
                <span class="hinta-muted">{{ $calendar->format($update->created_at, withTime: true) }}</span>
            </header>
            <p class="hinta-msg-body">{{ $update->body() }}</p>
        </article>
    @empty
        <p class="hinta-empty">{{ __('status.admin.no_updates') }}</p>
    @endforelse
</div>
