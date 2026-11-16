@php
    /** @var \App\Domain\Support\Models\Ticket $ticket */
    $ticket = $getRecord();
    $messages = $ticket->messages()->with('author')->get();
@endphp
{{-- ADR-0017: the conversation, oldest first: the requester's words, staff replies, and internal notes marked as such. --}}
<div class="hinta-thread">
    @if (($ticket->files ?? []) !== [])
        <p class="hinta-muted hinta-thread-files">{{ __('support.admin.request_files', ['count' => count($ticket->files)]) }}</p>
    @endif
    @forelse ($messages as $message)
        <article @class(['hinta-msg', 'hinta-msg-requester' => $message->from_requester, 'hinta-msg-note' => $message->internal])>
            <header class="hinta-msg-head">
                <span class="hinta-strong">
                    @if ($message->from_requester)
                        {{ __('support.admin.from_requester') }}
                    @else
                        {{ $message->author?->name ?? __('support.admin.staff') }}
                    @endif
                </span>
                @if ($message->internal)
                    <span class="hinta-msg-tag">{{ __('support.admin.internal') }}</span>
                @endif
                <span class="hinta-muted">{{ app(\App\Services\Calendar\EthiopianCalendar::class)->format($message->created_at, withTime: true) }}</span>
            </header>
            <p class="hinta-msg-body">{{ $message->body }}</p>
            @if (($message->files ?? []) !== [])
                <p class="hinta-muted">{{ __('support.admin.message_files', ['count' => count($message->files)]) }}</p>
            @endif
        </article>
    @empty
        <p class="hinta-empty">{{ __('support.admin.no_messages') }}</p>
    @endforelse
</div>
