<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Forms\Actions\SaveForm;
use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Models\FormSubmission;
use App\Domain\Forms\Support\FormField;
use App\Domain\Forms\Support\SubmissionAbout;
use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Models\Opportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Support\SpamGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/*
 * ADR-0053: calls, vacancies and internships can take applications through a portal form, answered from the Inbox.
 * Tenders never take one.
 */

function opportunityApplyForm(): Form
{
    return app(SaveForm::class)->handle(new Form, [
        'type' => 'vacancy',
        'response_days' => 5,
        'translations' => ['en' => ['title' => 'Sample application form', 'success_message' => 'Sample thanks', 'autoreply_subject' => 'Sample received', 'published' => true]],
        'fields' => [['key' => 'motivation', 'type' => FormField::TEXTAREA, 'required' => true, 'label' => ['en' => 'Sample motivation']]],
    ], staff(SystemRole::CommunicationsEditor));
}

/**
 * @param  array<string, mixed>  $data
 */
function openCall(string $type = 'vacancy', array $data = []): Opportunity
{
    return app(SaveOpportunity::class)->handle(new Opportunity, [
        'type' => $type,
        'closes_at' => CarbonImmutable::now()->addDays(20)->toDateTimeString(),
        'translations' => ['en' => ['title' => "Sample {$type} to apply to", 'summary' => 'Sample summary.', 'published' => true]],
        ...$data,
    ], staff(SystemRole::ProcurementOfficer));
}

/**
 * @return array<string, mixed>
 */
function applicationPost(?string $about): array
{
    return array_filter([
        'name' => 'Sample Applicant', 'email' => 'applicant@example.org', 'about' => $about,
        'answers' => ['motivation' => 'Sample motivation text'],
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
    ], fn (mixed $value): bool => $value !== null);
}

beforeEach(fn () => Mail::fake());

it('takes applications on the portal form and shows staff the opportunity in the Inbox', function () {
    $form = opportunityApplyForm();
    $vacancy = openCall(data: ['apply_form_id' => $form->id, 'apply_url' => 'https://example.org/jobs']);
    $token = SubmissionAbout::token(SubmissionAbout::OPPORTUNITY, $vacancy->id);

    // The portal form comes first, before the external link.
    $this->get('/en/opportunities/'.$vacancy->translation('en')->slug)->assertOk()
        ->assertSee(__('opportunities.apply', [], 'en'))
        ->assertSee('/en/forms/sample-application-form?about='.urlencode($token), escape: false)
        ->assertDontSee(__('opportunities.apply_online', [], 'en'));

    $this->get('/en/forms/sample-application-form?about='.urlencode($token))->assertOk()
        ->assertSee('Sample vacancy to apply to')
        ->assertSee('name="about" value="'.$token.'"', escape: false);

    $this->post('/en/forms/sample-application-form', applicationPost($token))->assertRedirect();
    $submission = FormSubmission::query()->sole();
    expect($submission->about_type)->toBe(SubmissionAbout::OPPORTUNITY)->and($submission->about_id)->toBe($vacancy->id);

    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    $this->get(SubmissionResource::getUrl('view', ['record' => $submission]))->assertOk()
        ->assertSee(__('forms.about.opportunity', [], 'en'))
        ->assertSee('Sample vacancy to apply to');
});

it('refuses applications to the form without the opportunity, or once it has closed', function () {
    $form = opportunityApplyForm();
    $vacancy = openCall(data: ['apply_form_id' => $form->id]);
    $token = SubmissionAbout::token(SubmissionAbout::OPPORTUNITY, $vacancy->id);

    // The bare form address shows that applications are closed and takes nothing.
    $this->get('/en/forms/sample-application-form')->assertOk()
        ->assertSee(__('forms.applications_closed_title', [], 'en'))
        ->assertDontSee('name="answers[motivation]"', escape: false);
    $this->post('/en/forms/sample-application-form', applicationPost(null))->assertSessionHasErrors('form');

    $this->travelTo(CarbonImmutable::now()->addDays(21));
    $this->get('/en/opportunities/'.$vacancy->translation('en')->slug)->assertOk()->assertDontSee('sample-application-form', escape: false);
    $this->post('/en/forms/sample-application-form', applicationPost($token))->assertSessionHasErrors('form');
    expect(FormSubmission::query()->count())->toBe(0);
});

it('never gives a tender a web form and offers a question instead', function () {
    $form = opportunityApplyForm();
    $tender = openCall('tender', ['apply_form_id' => $form->id]);

    expect($tender->apply_form_id)->toBeNull();
    $this->get('/en/opportunities/'.$tender->translation('en')->slug)->assertOk()
        ->assertSee(__('opportunities.ask', [], 'en'))
        ->assertSee('/en/contact', escape: false)
        ->assertDontSee(__('opportunities.apply', [], 'en').'</a>', escape: false);
});

it('offers a question when an open call has no way to apply', function () {
    $call = openCall('call');

    $this->get('/en/opportunities/'.$call->translation('en')->slug)->assertOk()->assertSee(__('opportunities.ask', [], 'en'));
});

it('lets the admin pick the form, and refuses one that is not active', function () {
    $form = opportunityApplyForm();
    $vacancy = openCall();
    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));

    livewire(EditOpportunity::class, ['record' => $vacancy->getRouteKey()])
        ->assertFormFieldExists('apply_form_id')
        ->fillForm(['apply_form_id' => $form->id])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($vacancy->refresh()->apply_form_id)->toBe($form->id);

    $form->forceFill(['active' => false])->save();
    expect(fn () => openCall(data: ['apply_form_id' => $form->id]))->toThrow(ValidationException::class);
});
