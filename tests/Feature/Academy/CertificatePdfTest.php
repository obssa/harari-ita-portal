<?php

use App\Domain\Academy\Actions\EnrolInCohort;
use App\Domain\Academy\Actions\IssueCertificate;
use App\Domain\Academy\Actions\RenderCertificatePdf;
use App\Domain\Academy\Actions\RevokeCertificate;
use App\Domain\Academy\Models\Certificate;
use App\Domain\Access\Enums\SystemRole;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Services\Sms\SendSms;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

/*
 * ADR-0030 addendum: the learner downloads their certificate as an A4 landscape PDF, and staff download the same
 * file from the admin.
 */

beforeEach(function () {
    Mail::fake();
    Bus::fake([SendSms::class]);
});

/** @return array{0: Certificate, 1: string} a certificate and its learner's private token */
function pdfCertificate(string $locale = 'en', string $name = 'Sample Pdf Learner'): array
{
    $cohort = sampleCohort(sampleCourse('Sample PDF course', ['signatory_name' => 'Sample Signatory', 'signatory_titles' => ['en' => 'Sample title']]), [
        'enrol_opens_at' => CarbonImmutable::now()->subMonth()->toDateTimeString(),
    ]);
    $enrolment = app(EnrolInCohort::class)->handle($cohort, ['name' => $name, 'phone' => '0911 000 777', 'consent' => true], $locale)->enrolment;
    $start = CarbonImmutable::today()->subWeek();
    $cohort->forceFill(['starts_on' => $start, 'ends_on' => $start->addDays(2)])->save();

    $certificate = app(IssueCertificate::class)->handle($enrolment->refresh(), staff(SystemRole::AcademyCoordinator), notify: false);

    return [$certificate, (string) $enrolment->token];
}

it('lets the learner download their certificate as a one-page PDF named after its code', function () {
    [$certificate, $token] = pdfCertificate();

    $this->get(route('enrolments.show', ['locale' => 'en', 'token' => $token]))->assertOk()
        ->assertSee(route('enrolments.certificate.pdf', ['locale' => 'en', 'token' => $token]), escape: false);
    $this->get(route('enrolments.certificate', ['locale' => 'en', 'token' => $token]))->assertOk()
        ->assertSee(__('academy.certificate.download', [], 'en'));

    $response = $this->get(route('enrolments.certificate.pdf', ['locale' => 'en', 'token' => $token]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Content-Disposition', 'attachment; filename="certificate-'.$certificate->code.'.pdf"');
    expect($response->getContent())->toStartWith('%PDF-');

    $plain = app(RenderCertificatePdf::class)->handle($certificate, compress: false);
    expect(preg_match_all('#/Type\s*/Page[^s]#', $plain))->toBe(1);

    $html = view('pdf.certificate', ['certificate' => $certificate->refresh(), 'fonts' => resource_path('fonts/pdf')])->render();
    expect($html)->toContain('Sample Pdf Learner')->toContain($certificate->code)->toContain('Sample PDF course')
        ->toContain('Sample Signatory')->toContain('data:image/png;base64,');
});

it('draws an Amharic certificate with the Ethiopic font for every Ethiopic run', function () {
    [$certificate] = pdfCertificate('am', 'ናሙና ተማሪ Sample');

    expect(app(RenderCertificatePdf::class)->handle($certificate))->toStartWith('%PDF-');
    $html = view('pdf.certificate', ['certificate' => $certificate, 'fonts' => resource_path('fonts/pdf')])->render();
    expect($html)->toContain('<span class="eth">ናሙና ተማሪ</span> Sample')
        ->toContain('<span class="eth">'.e(__('academy.certificate.title', [], 'am')).'</span>');
    expect((string) RenderCertificatePdf::text('<b>ሀ</b>'))->toBe('&lt;b&gt;<span class="eth">ሀ</span>&lt;/b&gt;');
});

it('refuses the PDF of a revoked certificate or an unknown learner', function () {
    [$certificate, $token] = pdfCertificate();
    app(RevokeCertificate::class)->handle($certificate, 'Sample reason', staff(SystemRole::AcademyCoordinator));

    $this->get(route('enrolments.certificate.pdf', ['locale' => 'en', 'token' => $token]))->assertNotFound();
    $this->get(route('enrolments.certificate.pdf', ['locale' => 'en', 'token' => str_repeat('a', 40)]))->assertNotFound();
});

it('lets staff who can see a certificate download it from the admin, but not a revoked one', function () {
    [$certificate] = pdfCertificate();
    [$revoked] = pdfCertificate('en', 'Sample Revoked Learner');
    $coordinator = staff(SystemRole::AcademyCoordinator);
    app(RevokeCertificate::class)->handle($revoked, 'Sample reason', $coordinator);
    $this->actingAs($coordinator);

    livewire(ListCertificates::class)
        ->assertTableActionVisible('download', $certificate)
        ->assertTableActionHidden('download', $revoked->refresh())
        ->callTableAction('download', $certificate)
        ->assertFileDownloaded('certificate-'.$certificate->code.'.pdf');
});
