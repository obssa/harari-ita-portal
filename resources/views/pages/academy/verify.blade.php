@php
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
@endphp
<x-layouts.public :title="__('academy.verify.title')" :description="__('academy.verify.lead')">
    {{-- ADR-0030: check a certificate by its code (or the QR code, which opens this page with the code): valid, revoked
         or not found. A valid certificate shows the holder's initials only. --}}
    <x-page-hero :title="__('academy.verify.title')" :lead="__('academy.verify.lead')" :kicker="__('academy.title')" tone="night"
                 :crumbs="[[__('academy.title'), route('academy.index')]]">
        <form method="get" action="{{ route('certificates.verify') }}" class="flex max-w-xl flex-wrap gap-2">
            <label for="verify-code" class="sr-only">{{ __('academy.verify.code_label') }}</label>
            <input id="verify-code" type="text" name="code" value="{{ $typed ?? $code }}" maxlength="20" required placeholder="{{ __('academy.verify.placeholder') }}" autocomplete="off" spellcheck="false" dir="ltr"
                   @if ($invalid) aria-invalid="true" aria-describedby="verify-error" @endif
                   class="min-w-0 flex-1 rounded-control border border-night-line bg-night-950 px-5 py-3 font-mono text-base text-night-ink uppercase placeholder:text-night-muted placeholder:normal-case focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/40 focus:outline-none">
            <x-ui.button type="submit" variant="action" size="lg">{{ __('academy.verify.submit') }}</x-ui.button>
        </form>
        @if ($invalid)
            <p id="verify-error" class="text-sm text-saffron-300">{{ __('academy.verify.invalid') }}</p>
        @endif
    </x-page-hero>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        @if ($state === 'valid' && $certificate)
            <section role="status" aria-labelledby="verify-result" class="rounded-card border border-success-600/40 bg-surface p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <span aria-hidden="true" class="grid size-10 place-items-center rounded-full bg-success-600 text-white">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <h2 id="verify-result" class="heading-2">{{ __('academy.verify.valid') }}</h2>
                </div>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="kicker">{{ __('academy.field.code') }}</dt>
                        <dd class="mt-1 font-mono text-lg font-semibold">{{ $certificate->code }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('academy.verify.holder') }}</dt>
                        <dd class="mt-1 text-lg font-semibold">{{ $certificate->holder_initials }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="kicker">{{ __('academy.admin.course') }}</dt>
                        <dd class="mt-1 text-lg font-semibold">{{ $courseTitle }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('academy.cohort.dates') }}</dt>
                        <dd class="mt-1">{{ __('academy.cohort.runs', ['start' => $calendar->format($certificate->cohort->starts_on), 'end' => $calendar->format($certificate->cohort->ends_on)]) }}</dd>
                    </div>
                    <div>
                        <dt class="kicker">{{ __('academy.field.issued_at') }}</dt>
                        <dd class="mt-1"><x-date :date="$certificate->issued_at" /></dd>
                    </div>
                </dl>
                <p class="mt-6 text-sm text-ink-muted">{{ __('academy.verify.initials_note') }}</p>
            </section>
        @elseif ($state === 'revoked')
            <section role="status" class="rounded-card border border-danger-600/40 bg-surface p-6 sm:p-8">
                <h2 class="heading-2">{{ __('academy.verify.revoked') }}</h2>
                <p class="mt-3 text-ink-muted">{{ __('academy.verify.revoked_help', ['code' => $code]) }}</p>
            </section>
        @elseif ($state === 'unknown')
            <section role="status" class="rounded-card border border-line bg-surface p-6 sm:p-8">
                <h2 class="heading-2">{{ __('academy.verify.unknown') }}</h2>
                <p class="mt-3 text-ink-muted">{{ __('academy.verify.unknown_help', ['code' => $code]) }}</p>
            </section>
        @else
            <p class="text-ink-muted">{{ __('academy.verify.how') }}</p>
        @endif
    </div>
</x-layouts.public>
