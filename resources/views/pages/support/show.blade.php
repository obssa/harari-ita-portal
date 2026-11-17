@use('App\Domain\Support\Enums\TicketStatus')
@php
    $field = 'mt-1 block w-full rounded-control border border-line bg-surface px-3 py-2 focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-500/30';
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $steps = [__('support.public.stage.received'), __('support.public.stage.in_progress'), __('support.public.stage.resolved')];
    $current = match ($ticket->status) {
        TicketStatus::New => 0,
        TicketStatus::InProgress, TicketStatus::Waiting => 1,
        TicketStatus::Resolved, TicketStatus::Closed => 3,
    };
@endphp
<x-layouts.public :title="$ticket->reference" :description="__('support.track.description')">
    {{-- ADR-0017: the request as the requester sees it: where it stands, the conversation without internal notes,
         and the way to write back or rate the help. --}}
    <x-page-hero :title="$ticket->reference" :kicker="__('support.track.title')" tone="night" :crumbs="[[__('support.public.title'), route('support.create')]]">
        <x-ui.stepper :steps="$steps" :current="$current" tone="night" class="max-w-3xl" />
    </x-page-hero>

    <div class="mx-auto max-w-page px-4 pt-10 pb-16 sm:px-6">
        @if (session('status'))
            <x-ui.alert tone="success" class="mb-8 max-w-3xl">{{ session('status') }}</x-ui.alert>
        @endif

        <div class="grid gap-12 lg:grid-cols-[1fr_20rem]">
            <div class="min-w-0 max-w-3xl space-y-8">
                <article class="rounded-card border border-line bg-surface p-6">
                    <p class="kicker">{{ $ticket->category->label() }}</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ $ticket->subject }}</h2>
                    <p class="mt-4 whitespace-pre-line">{{ $ticket->description }}</p>
                    @if (($ticket->files ?? []) !== [])
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($ticket->files as $index => $file)
                                <li><a href="{{ route('support.file', ['reference' => $ticket->reference, 'key' => 't'.$index]) }}" class="inline-flex items-center gap-2 rounded-control border border-line px-3 py-1.5 font-mono text-xs hover:border-cyan-500">{{ $file['name'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="mt-4 font-mono text-xs text-ink-muted">{{ __('support.field.created_at') }}: {{ $calendar->format($ticket->created_at, withTime: true) }}</p>
                </article>

                <section aria-labelledby="ticket-messages">
                    <h2 id="ticket-messages" class="heading-3">{{ __('support.public.conversation') }}</h2>
                    @if ($messages->isEmpty())
                        <p class="mt-3 text-ink-muted">{{ __('support.public.no_reply') }}</p>
                    @else
                        <ol class="mt-4 space-y-3">
                            @foreach ($messages as $message)
                                <li @class([
                                    'rounded-card border p-5',
                                    'border-line bg-surface' => $message->from_requester,
                                    'border-cyan-600/40 bg-cyan-50 dark:bg-night-800' => ! $message->from_requester,
                                ])>
                                    <p class="flex flex-wrap items-baseline justify-between gap-2 font-mono text-xs text-ink-muted">
                                        <span class="font-semibold text-ink uppercase">{{ $message->from_requester ? __('support.public.you') : __('support.public.desk') }}</span>
                                        <span>{{ $calendar->format($message->created_at, withTime: true) }}</span>
                                    </p>
                                    <p class="mt-2 whitespace-pre-line">{{ $message->body }}</p>
                                    @if (($message->files ?? []) !== [])
                                        <ul class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($message->files as $index => $file)
                                                <li><a href="{{ route('support.file', ['reference' => $ticket->reference, 'key' => 'm'.$message->id.'-'.$index]) }}" class="inline-flex items-center gap-2 rounded-control border border-line px-3 py-1.5 font-mono text-xs hover:border-cyan-500">{{ $file['name'] }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                @if ($ticket->status === TicketStatus::Resolved)
                    <section aria-labelledby="ticket-rate" class="rounded-card border border-line bg-surface p-6">
                        <h2 id="ticket-rate" class="heading-3">{{ __('support.public.rate_title') }}</h2>
                        <p class="mt-1 text-ink-muted">{{ __('support.public.rate_text') }}</p>
                        <form method="post" action="{{ route('support.rate', ['reference' => $ticket->reference]) }}" class="mt-4 space-y-4">
                            @csrf
                            <fieldset @error('satisfaction') aria-describedby="satisfaction-error" @enderror>
                                <legend class="sr-only">{{ __('support.field.satisfaction') }}</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (range(1, 5) as $score)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="satisfaction" value="{{ $score }}" class="peer sr-only" @checked((int) old('satisfaction') === $score) required>
                                            <span class="grid size-11 place-items-center rounded-control border border-line font-mono peer-checked:border-primary-700 peer-checked:bg-primary-700 peer-checked:text-white peer-focus-visible:outline-3 dark:peer-checked:bg-cyan-300 dark:peer-checked:text-ink-brand">
                                                {{ $score }}<span class="sr-only"> / 5</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 font-mono text-xs text-ink-muted">{{ __('support.public.rate_scale') }}</p>
                                @error('satisfaction')<p id="satisfaction-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </fieldset>
                            <div>
                                <label for="note" class="font-medium">{{ __('support.public.rate_note') }}</label>
                                <textarea id="note" name="note" rows="3" maxlength="1000" class="{{ $field }}">{{ old('note') }}</textarea>
                            </div>
                            <x-ui.button type="submit">{{ __('support.public.rate_submit') }}</x-ui.button>
                        </form>
                    </section>
                @endif

                @if ($ticket->status !== TicketStatus::Closed)
                    <section aria-labelledby="ticket-reply" class="rounded-card border border-line bg-surface p-6">
                        <h2 id="ticket-reply" class="heading-3">{{ $ticket->status === TicketStatus::Resolved ? __('support.public.reopen_title') : __('support.public.reply_title') }}</h2>
                        <form method="post" action="{{ route('support.reply', ['reference' => $ticket->reference]) }}" enctype="multipart/form-data" class="mt-4 space-y-4" novalidate>
                            @csrf
                            <div>
                                <label for="body" class="sr-only">{{ __('support.public.reply_title') }}</label>
                                <textarea id="body" name="body" rows="5" required maxlength="5000" @error('body') aria-invalid="true" aria-describedby="body-error" @enderror class="{{ $field }}">{{ old('body') }}</textarea>
                                @error('body')<p id="body-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="reply-files" class="text-sm font-medium">{{ __('support.public.files') }}</label>
                                <input id="reply-files" name="files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                       @error('files') aria-invalid="true" aria-describedby="reply-files-error" @enderror
                                       class="mt-1 block w-full text-sm file:me-4 file:rounded-control file:border-0 file:bg-night-900 file:px-4 file:py-2 file:font-mono file:text-xs file:text-cyan-300 file:uppercase">
                                @error('files')<p id="reply-files-error" class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                            </div>
                            <x-forms.protection :turnstile-key="null" />
                            @error('form')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                            <x-ui.button type="submit">{{ __('support.public.reply_submit') }}</x-ui.button>
                        </form>
                    </section>
                @endif
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('support.admin.handling') }}">
                <section class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink">
                    <x-hex-field id="ticket-hex" fade="top" class="absolute inset-x-0 top-0 -z-10 h-32 w-full text-cyan-500/20" />
                    <h2 class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('support.field.status') }}</h2>
                    <p class="mt-2 font-display text-2xl font-semibold tracking-tight">{{ $ticket->status->label() }}</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        @if ($ticket->status->isOpen() && $ticket->first_response_at === null && $ticket->response_due_at)
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('support.field.response_due_at') }}</dt>
                                <dd class="mt-1">{{ $calendar->format($ticket->response_due_at, withTime: true) }}</dd>
                            </div>
                        @endif
                        @if ($service)
                            <div>
                                <dt class="font-mono text-[11px] tracking-wider text-night-muted uppercase">{{ __('support.public.service') }}</dt>
                                <dd class="mt-1">{{ $service->getAttribute('title') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
                <p class="text-sm text-ink-muted">{{ __('support.public.keep_reference') }}</p>
            </aside>
        </div>
    </div>
</x-layouts.public>
