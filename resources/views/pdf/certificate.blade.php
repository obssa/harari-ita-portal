{{-- ADR-0030 addendum: the certificate as a PDF, drawn by dompdf. The same design as components/academy/certificate,
     rebuilt in millimetres with absolute positions and tables (dompdf has no flex or container units). Every text
     goes through RenderCertificatePdf::text so Ethiopic letters get the Ethiopic font. --}}
@php
    /** @var \App\Domain\Academy\Models\Certificate $certificate */
    $t = fn (?string $text) => \App\Domain\Academy\Actions\RenderCertificatePdf::text($text);
    $locale = $certificate->enrolment?->locale ?? app()->getLocale();
    $calendar = app(\App\Services\Calendar\EthiopianCalendar::class);
    $course = $certificate->course;
    $cohort = $certificate->cohort;
    $shown = \App\Domain\Organisation\Queries\PublicDirectory::shown($course, $locale);
    $courseTitle = $shown instanceof \App\Domain\Academy\Models\CourseTranslation ? $shown->title : $course->title($locale);
    $signatoryTitle = $course->signatoryTitle($locale);
    $verifyAt = preg_replace('#^https?://#', '', route('certificates.verify', ['locale' => $locale]));
    $dates = __('academy.certificate.dates', ['start' => $calendar->format($cohort->starts_on, $locale), 'end' => $calendar->format($cohort->ends_on, $locale)], $locale);
    if ($course->duration_hours) {
        $dates .= ' · '.trans_choice('academy.certificate.hours', $course->duration_hours, ['count' => $course->duration_hours], $locale);
    }
    // The "Jugol circuit" (ADR-0014) as a faint corner ornament.
    $traces = ['M0 40h120l30 30h110l30-30h110', 'M0 100h80l30 30h90', 'M400 160h-90l-30-30h-50', 'M260 70v60l20 20', 'M150 70v80l-20 20H0', 'M330 40V0', 'M60 40V0M60 100v40l20 20', 'M400 100h-50l-20-20'];
    $circuit = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 200" width="400" height="200"><g fill="none" stroke="#105d6f" stroke-opacity="0.11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        .implode('', array_map(fn (string $d): string => '<path d="'.$d.'"/>', $traces))
        .'</g><g fill="#e8912d" fill-opacity="0.35"><circle cx="200" cy="130" r="4"/><circle cx="230" cy="130" r="4"/><circle cx="80" cy="160" r="4"/><circle cx="330" cy="80" r="4"/></g></svg>');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<title>{{ __('academy.certificate.title', [], $locale) }} {{ $certificate->code }}</title>
<style>
    @font-face { font-family: 'inter'; font-weight: normal; src: url('file://{{ $fonts }}/Inter-Regular.ttf'); }
    @font-face { font-family: 'inter'; font-weight: bold; src: url('file://{{ $fonts }}/Inter-Bold.ttf'); }
    @font-face { font-family: 'grotesk'; font-weight: normal; src: url('file://{{ $fonts }}/SpaceGrotesk-SemiBold.ttf'); }
    @font-face { font-family: 'mono'; font-weight: normal; src: url('file://{{ $fonts }}/JetBrainsMono-Medium.ttf'); }
    @font-face { font-family: 'ethiopic'; font-weight: normal; src: url('file://{{ $fonts }}/NotoSansEthiopic-Regular.ttf'); }
    @font-face { font-family: 'ethiopic'; font-weight: bold; src: url('file://{{ $fonts }}/NotoSansEthiopic-Bold.ttf'); }

    @page { size: a4 landscape; margin: 0; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'inter'; color: #04121a; font-size: 10.5pt; line-height: 1.35; }
    .eth { font-family: 'ethiopic'; letter-spacing: 0; text-transform: none; }
    .display { font-family: 'grotesk'; }
    .display .eth { font-weight: bold; }
    .mono { font-family: 'mono'; text-transform: uppercase; }
    .muted { color: #143c49; }

    .sheet { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; overflow: hidden; }
    .frame { position: absolute; top: 7mm; left: 7mm; right: 7mm; bottom: 7mm; border: 0.9mm solid #105d6f; border-radius: 2mm; }
    .rule { position: absolute; top: 9.5mm; left: 9.5mm; right: 9.5mm; bottom: 9.5mm; border: 0.3mm solid #e8912d; border-radius: 1.4mm; }
    .ornament-top { position: absolute; top: 12mm; left: 199mm; width: 86mm; height: 43mm; }

    .header { position: absolute; top: 18mm; left: 22mm; width: 253mm; }
    .header td { vertical-align: top; }
    .logo { height: 21mm; }
    .academy { text-align: right; font-size: 8.5pt; letter-spacing: 1.4pt; color: #105d6f; padding-top: 2mm; }

    .body { position: absolute; top: 48mm; left: 22mm; width: 253mm; text-align: center; }
    .eyebrow { font-size: 9pt; letter-spacing: 2.4pt; color: #aa5c00; margin: 0; }
    .title { font-size: 30pt; line-height: 1.15; margin: 2.5mm 0 0; }
    .certifies { font-size: 11.5pt; margin: 6mm 0 0; }
    .holder { display: inline-block; font-size: 27pt; line-height: 1.2; margin: 2.5mm 0 0; padding: 0 10mm 1.8mm; border-bottom: 0.45mm solid #e8912d; }
    .completed { font-size: 11.5pt; margin: 4.5mm 0 0; }
    .course { font-size: 19pt; line-height: 1.25; color: #105d6f; margin: 2mm 18mm 0; }
    .dates { font-size: 10pt; margin: 3.5mm 0 0; }
    .partner { font-size: 9.5pt; margin: 1.5mm 0 0; }

    .footer { position: absolute; left: 22mm; width: 253mm; top: 160mm; }
    .footer td { vertical-align: bottom; }
    .label { font-size: 7.5pt; letter-spacing: 1.2pt; color: #143c49; }
    .signatory { width: 62mm; border-top: 0.3mm solid #04121a; padding-top: 1.6mm; }
    .signatory-name { font-weight: bold; font-size: 10.5pt; }
    .signatory-title { font-size: 9pt; }
    .issued { font-weight: bold; font-size: 10.5pt; margin-top: 1mm; white-space: nowrap; }
    .code { font-family: 'mono'; font-size: 11pt; margin-top: 1mm; letter-spacing: 0.6pt; }
    .verify { font-size: 7.5pt; margin-top: 1mm; }
    .qr { width: 25mm; height: 25mm; }
</style>
</head>
<body>
<div class="sheet">
    <img class="ornament-top" src="{{ $circuit }}" alt="">
    <div class="frame"></div>
    <div class="rule"></div>

    <table class="header" cellpadding="0" cellspacing="0">
        <tr>
            <td><img class="logo" src="{{ public_path('brand/lockup-horizontal.png') }}" alt="{{ __('site.name', [], $locale) }}"></td>
            <td class="academy mono">{{ $t(__('academy.certificate.academy', [], $locale)) }}</td>
        </tr>
    </table>

    <div class="body">
        <p class="eyebrow mono">{{ $t(__('academy.certificate.eyebrow', [], $locale)) }}</p>
        <p class="title display">{{ $t(__('academy.certificate.title', [], $locale)) }}</p>
        <p class="certifies muted">{{ $t(__('academy.certificate.certifies', [], $locale)) }}</p>
        <p class="holder display">{{ $t($certificate->holder_name ?? '—') }}</p>
        <p class="completed muted">{{ $t(__('academy.certificate.completed', [], $locale)) }}</p>
        <p class="course display">{{ $t($courseTitle) }}</p>
        <p class="dates muted">{{ $t($dates) }}</p>
        @if (filled($course->partner))
            <p class="partner muted">{{ $t(__('academy.certificate.partner', ['partner' => $course->partner], $locale)) }}</p>
        @endif
    </div>

    <table class="footer" cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%">
                @if (filled($course->signatory_name))
                    <div class="signatory">
                        <div class="signatory-name">{{ $t($course->signatory_name) }}</div>
                        @if ($signatoryTitle !== '')
                            <div class="signatory-title muted">{{ $t($signatoryTitle) }}</div>
                        @endif
                    </div>
                @endif
            </td>
            <td width="34%" style="text-align: center;">
                <div class="label mono">{{ $t(__('academy.certificate.issued_on', [], $locale)) }}</div>
                <div class="issued">{{ $t($calendar->format($certificate->issued_at, $locale)) }}</div>
            </td>
            <td width="33%" style="text-align: right;">
                <table cellpadding="0" cellspacing="0" align="right">
                    <tr>
                        <td style="text-align: right; padding-right: 3.5mm;">
                            <div class="label mono">{{ $t(__('academy.certificate.code', [], $locale)) }}</div>
                            <div class="code">{{ $certificate->code }}</div>
                            <div class="verify muted">{{ $t(__('academy.certificate.verify_at', ['url' => $verifyAt], $locale)) }}</div>
                        </td>
                        <td><img class="qr" src="{{ \App\Support\QrCode::pngDataUri($certificate->verifyUrl($locale)) }}" alt="{{ __('academy.certificate.qr_alt', [], $locale) }}"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
