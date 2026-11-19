@use('App\Domain\Academy\Enums\EnrolmentStatus')
@php
    $status = $enrolment->status;
    $title = $translation?->title ?? $course->title();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
@endphp
<x-layouts.public :title="__('academy.learner.title')" :alternates="[]">
    {{-- ADR-0029: the learner's own page, reached only by their private link: status, the sessions with their
         attendance, and the certificate once issued. --}}
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        @if (session('status'))
            <p role="status" class="mb-6 rounded-card border border-primary-600/40 bg-primary-50 px-4 py-3 text-primary-800 dark:bg-primary-950 dark:text-primary-200">{{ session('status') }}</p>
        @endif

        <article class="relative isolate overflow-hidden rounded-card border border-night-line bg-night-900 p-6 text-night-ink sm:p-8">
            <x-hex-field id="learner-hex" fade="right" class="absolute inset-y-0 end-0 -z-10 h-full w-1/2 text-cyan-500/20" />
            <p class="kicker [--color-eyebrow:var(--color-cyan-300)]">{{ __('academy.learner.title') }}</p>
            <h1 class="mt-2 font-display text-2xl font-semibold tracking-tight sm:text-3xl" @if ($translation) lang="{{ $translation->locale }}" @endif>{{ $title }}</h1>
            <p class="mt-2 text-night-muted">{{ $cohort->name }} · {{ __('academy.cohort.runs', ['start' => $calendar->format($cohort->starts_on), 'end' => $calendar->format($cohort->ends_on)]) }}</p>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <x-ui.badge :tone="$status->tone()">{{ $status->label() }}</x-ui.badge>
                <span class="font-medium">{{ $enrolment->name }}</span>
            </div>
            @if ($cohort->isCancelled())
                <p class="mt-4 text-night-muted">{{ __('academy.learner.cohort_cancelled') }}</p>
            @elseif ($status === EnrolmentStatus::Waitlisted)
                <p class="mt-4">{{ __('academy.learner.waitlist_position', ['position' => $position]) }}</p>
            @elseif ($status === EnrolmentStatus::Cancelled)
                <p class="mt-4 text-night-muted">{{ __('academy.learner.cancelled') }}</p>
            @endif
        </article>

        @if ($certificate)
            <section aria-labelledby="learner-certificate" class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-card border border-success-600/50 border-s-4 border-s-success-600 bg-surface p-5">
                <div>
                    <h2 id="learner-certificate" class="font-display text-lg font-semibold">{{ __('academy.learner.certificate_ready') }}</h2>
                    <p class="mt-1 text-sm">{{ __('academy.certificate.code') }}: <span class="font-mono font-semibold">{{ $certificate->code }}</span> · <x-date :date="$certificate->issued_at" /></p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route('enrolments.certificate.pdf', ['token' => $token])" download>{{ __('academy.certificate.download') }}</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('enrolments.certificate', ['token' => $token])">{{ __('academy.learner.open_certificate') }}</x-ui.button>
                </div>
            </section>
        @endif

        @if ($status === EnrolmentStatus::Enrolled && $sessions->isNotEmpty())
            <section aria-labelledby="learner-sessions" class="mt-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="learner-sessions" class="heading-3">{{ trans_choice('academy.cohort.sessions', $sessions->count(), ['count' => $sessions->count()]) }}</h2>
                    @if ($percent !== null && $attended !== [])
                        <p class="font-mono text-xs tracking-wide text-ink-muted uppercase">{{ __('academy.learner.attended', ['count' => count($attended), 'total' => $sessions->count(), 'percent' => $percent]) }}</p>
                    @endif
                </div>
                <ol class="mt-4 divide-y divide-line overflow-hidden rounded-card border border-line bg-surface">
                    @foreach ($sessions as $session)
                        @php $present = in_array($session->id, $attended, true); @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <span>
                                <x-date :date="$session->starts_at" :time="true" />
                                @if ($session->title() !== '')
                                    <span class="block text-sm text-ink-muted">{{ $session->title() }}</span>
                                @endif
                            </span>
                            @if ($present)
                                <x-ui.badge tone="success">{{ __('academy.learner.present') }}</x-ui.badge>
                            @elseif ($session->hasStarted())
                                <x-ui.badge>{{ __('academy.learner.not_marked') }}</x-ui.badge>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @if ($translation)
                    <p class="mt-3 text-sm"><a href="{{ route('courses.ics', ['locale' => $translation->locale, 'slug' => $translation->slug, 'cohort' => $cohort->id]) }}" class="text-link underline underline-offset-4">{{ __('events.add_to_calendar') }}</a></p>
                @endif
            </section>
        @endif

        <div class="mt-6 flex flex-wrap gap-2 text-sm">
            @if ($translation)
                <a href="{{ route('courses.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}" class="rounded-control border border-line px-3 py-1.5 hover:bg-surface-muted">{{ __('academy.learner.course_page') }}</a>
            @endif
        </div>

        @if ($canCancel)
            <details class="mt-8 rounded-card border border-line p-4">
                <summary class="cursor-pointer font-medium">{{ __('academy.learner.cancel_title') }}</summary>
                <p class="mt-3 text-sm text-ink-muted">{{ __('academy.learner.cancel_help') }}</p>
                <form method="post" action="{{ route('enrolments.cancel', ['token' => $token]) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="rounded-control border border-danger-600 px-4 py-2 font-medium text-danger-600 hover:bg-danger-50">{{ __('academy.learner.cancel') }}</button>
                </form>
            </details>
        @endif

        <p class="mt-8 text-center text-sm text-ink-muted">{{ __('academy.learner.keep_link') }}</p>
    </div>
</x-layouts.public>
