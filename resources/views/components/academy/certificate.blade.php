@props(['certificate', 'locale' => null])
{{-- ADR-0030: one certificate on an A4 landscape sheet. Always on paper colours (not the site theme); every size is in
     container units, so the sheet scales on a phone and fills the page in print. The QR code opens the verification
     page, which shows initials only. --}}
@php
    /** @var \App\Domain\Academy\Models\Certificate $certificate */
    $locale ??= $certificate->enrolment?->locale ?? app()->getLocale();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $course = $certificate->course;
    $cohort = $certificate->cohort;
    $shown = \App\Domain\Organisation\Queries\PublicDirectory::shown($course, $locale);
    $courseTitle = $shown instanceof \App\Domain\Academy\Models\CourseTranslation ? $shown->title : $course->title($locale);
    $verifyUrl = $certificate->verifyUrl($locale);
    $signatoryTitle = $course->signatoryTitle($locale);
@endphp
<article {{ $attributes->class(['certificate-sheet @container relative w-full max-w-[297mm] overflow-hidden bg-white text-night-950 shadow-lg ring-1 ring-black/10 print:ring-0']) }}
         lang="{{ $locale }}" aria-label="{{ __('academy.certificate.title', [], $locale) }}">
    <div aria-hidden="true" class="absolute inset-[2.2cqw] rounded-[0.6cqw] border-[0.25cqw] border-primary-700"></div>
    <div aria-hidden="true" class="absolute inset-[3cqw] rounded-[0.4cqw] border-[0.08cqw] border-saffron-500"></div>
    <x-circuit class="absolute -end-[6cqw] -top-[4cqw] h-[22cqw] w-[44cqw] text-primary-700/15" />
    <x-circuit class="absolute -bottom-[5cqw] -start-[8cqw] h-[22cqw] w-[44cqw] rotate-180 text-primary-700/15" />

    <div class="relative flex h-full flex-col px-[7cqw] py-[5.5cqw]">
        <header class="flex items-start justify-between gap-[2cqw]">
            <img src="/brand/lockup-horizontal.webp" alt="{{ __('site.name', [], $locale) }}" width="920" height="370" class="h-[7cqw] w-auto">
            <p class="text-end font-mono text-[1.15cqw] uppercase tracking-[0.2em] text-primary-700">{{ __('academy.certificate.academy', [], $locale) }}</p>
        </header>

        <div class="mt-[3cqw] flex flex-1 flex-col items-center text-center">
            <p class="font-mono text-[1.3cqw] uppercase tracking-[0.3em] text-saffron-700">{{ __('academy.certificate.eyebrow', [], $locale) }}</p>
            <h1 class="mt-[0.8cqw] font-display text-[4.2cqw] font-semibold leading-tight">{{ __('academy.certificate.title', [], $locale) }}</h1>
            <p class="mt-[2cqw] text-[1.6cqw] text-night-600">{{ __('academy.certificate.certifies', [], $locale) }}</p>
            <p class="mt-[0.8cqw] border-b-[0.12cqw] border-saffron-500 px-[3cqw] pb-[0.6cqw] font-display text-[3.6cqw] font-semibold leading-tight">{{ $certificate->holder_name ?? '—' }}</p>
            <p class="mt-[1.6cqw] text-[1.6cqw] text-night-600">{{ __('academy.certificate.completed', [], $locale) }}</p>
            <p class="mt-[0.6cqw] max-w-[80%] font-display text-[2.6cqw] font-semibold leading-snug text-primary-700">{{ $courseTitle }}</p>
            <p class="mt-[1.2cqw] text-[1.35cqw] text-night-600">
                {{ __('academy.certificate.dates', ['start' => $calendar->format($cohort->starts_on, $locale), 'end' => $calendar->format($cohort->ends_on, $locale)], $locale) }}
                @if ($course->duration_hours)
                    · {{ trans_choice('academy.certificate.hours', $course->duration_hours, ['count' => $course->duration_hours], $locale) }}
                @endif
            </p>
            @if (filled($course->partner))
                <p class="mt-[0.5cqw] text-[1.25cqw] text-night-600">{{ __('academy.certificate.partner', ['partner' => $course->partner], $locale) }}</p>
            @endif
        </div>

        <footer class="mt-[2cqw] grid grid-cols-3 items-end gap-[2cqw]">
            <div class="text-start">
                @if (filled($course->signatory_name))
                    <div class="w-[20cqw] border-t-[0.1cqw] border-night-950 pt-[0.5cqw]">
                        <p class="text-[1.35cqw] font-semibold">{{ $course->signatory_name }}</p>
                        @if ($signatoryTitle !== '')
                            <p class="text-[1.1cqw] text-night-600">{{ $signatoryTitle }}</p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="text-center">
                <p class="font-mono text-[1cqw] uppercase tracking-[0.2em] text-night-600">{{ __('academy.certificate.issued_on', [], $locale) }}</p>
                <p class="mt-[0.3cqw] text-[1.3cqw] font-semibold">{{ $calendar->format($certificate->issued_at, $locale) }}</p>
            </div>
            <div class="flex items-end justify-end gap-[1.2cqw] text-end">
                <div>
                    <p class="font-mono text-[1cqw] uppercase tracking-[0.2em] text-night-600">{{ __('academy.certificate.code', [], $locale) }}</p>
                    <p class="mt-[0.3cqw] font-mono text-[1.4cqw] font-semibold">{{ $certificate->code }}</p>
                    <p class="mt-[0.3cqw] ms-auto max-w-[17cqw] text-[0.95cqw] leading-snug text-night-600 [overflow-wrap:anywhere]">{{ __('academy.certificate.verify_at', ['url' => preg_replace('#^https?://#', '', route('certificates.verify', ['locale' => $locale]))], $locale) }}</p>
                </div>
                <img src="{{ \App\Support\QrCode::dataUri($verifyUrl) }}" alt="{{ __('academy.certificate.qr_alt', [], $locale) }}" width="160" height="160" class="size-[9cqw] shrink-0">
            </div>
        </footer>
    </div>
</article>
