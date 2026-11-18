<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Forms\Actions\SaveForm;
use App\Domain\Forms\Models\Form;
use App\Domain\Forms\Models\FormSubmission;
use App\Domain\Forms\Support\FormField;
use App\Domain\Forms\Support\SubmissionAbout;
use App\Domain\Services\Actions\SaveServiceItem;
use App\Domain\Services\Models\ServiceItem;
use App\Domain\Support\Actions\OpenTicket;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Models\Ticket;
use App\Filament\Resources\Services\Pages\EditServiceItem;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Filament\Resources\Tickets\TicketResource;
use App\Support\SpamGuard;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/*
 * ADR-0053: every service can be requested from its own page, and the request reaches staff in the admin.
 */

/**
 * @param  array<string, mixed>  $data
 */
function requestableService(string $title = 'Sample service to request', array $data = [], bool $published = true): ServiceItem
{
    return app(SaveServiceItem::class)->handle(new ServiceItem, [
        'channels' => ['in_person'],
        'translations' => ['en' => ['title' => $title, 'steps' => "1. Sample first step\n2. Sample second step", 'published' => $published]],
        ...$data,
    ], staff(SystemRole::ServiceOwner));
}

function serviceRequestForm(): Form
{
    return app(SaveForm::class)->handle(new Form, [
        'type' => 'general',
        'response_days' => 3,
        'translations' => ['en' => ['title' => 'Sample service request form', 'success_message' => 'Sample thanks', 'autoreply_subject' => 'Sample received', 'published' => true]],
        'fields' => [['key' => 'detailsfld', 'type' => FormField::TEXTAREA, 'required' => true, 'label' => ['en' => 'Sample details']]],
    ], staff(SystemRole::CommunicationsEditor));
}

beforeEach(fn () => Mail::fake());

it('offers "Request this service" on an in-person service, leading to the desk with the service chosen', function () {
    $service = requestableService();
    $slug = $service->translation('en')->slug;

    $this->get("/en/services/{$slug}")->assertOk()
        ->assertSee(__('services.request', [], 'en'))
        ->assertSee(__('services.request_ready', [], 'en'))
        ->assertSee("/en/support/new?service={$slug}", escape: false);

    $this->get("/en/support/new?service={$slug}")->assertOk()
        ->assertSee(__('support.public.request_title', [], 'en'))
        ->assertSee(__('support.public.requesting', [], 'en'))
        ->assertSee('Sample service to request')
        ->assertSee('value="'.$service->id.'" selected', escape: false)
        ->assertSee('value="'.TicketCategory::ServiceRequest->value.'" selected', escape: false);
});

it('keeps "Start online" first for an online service and still offers the request', function () {
    $service = requestableService('Sample online service', ['channels' => ['online'], 'external_url' => 'https://example.org/start']);

    $this->get('/en/services/'.$service->translation('en')->slug)->assertOk()
        ->assertSeeInOrder([__('services.start_online', [], 'en'), __('services.request', [], 'en')]);
});

it('hides the button when the service is information only', function () {
    $service = requestableService('Sample information page', ['accepts_requests' => false]);
    $slug = $service->translation('en')->slug;

    $this->get("/en/services/{$slug}")->assertOk()->assertDontSee(__('services.request', [], 'en'));
    // The desk form opens as the ordinary IT support form.
    $this->get("/en/support/new?service={$slug}")->assertOk()
        ->assertSee(__('support.public.title', [], 'en'))
        ->assertDontSee(__('support.public.requesting', [], 'en'));
});

it('ignores an unknown or unpublished service in the address', function () {
    $draft = requestableService('Sample draft service', published: false);

    foreach (['no-such-service', $draft->translation('en')->slug, '<script>'] as $slug) {
        $this->get('/en/support/new?service='.urlencode($slug))->assertOk()
            ->assertDontSee(__('support.public.requesting', [], 'en'))
            ->assertDontSee('Sample draft service');
    }
});

it('files the request as a ticket about the service, which staff see in the admin', function () {
    $service = requestableService();

    $this->post('/en/support/new', [
        'requester_type' => 'resident',
        'name' => 'Sample Resident',
        'email' => 'resident@example.org',
        'category' => TicketCategory::ServiceRequest->value,
        'service_item_id' => $service->id,
        'subject' => 'Sample service to request',
        'description' => 'Sample: I would like to use this service for my office.',
        'consent' => '1',
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
    ])->assertRedirect();

    $ticket = Ticket::query()->sole();
    expect($ticket->service_item_id)->toBe($service->id)
        ->and($ticket->category)->toBe(TicketCategory::ServiceRequest);

    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    $this->get(TicketResource::getUrl('index'))->assertOk()->assertSee('Sample service to request');
    $this->get(TicketResource::getUrl('view', ['record' => $ticket]))->assertOk()
        ->assertSee(__('support.field.service', [], 'en'))
        ->assertSee(__('support.category.service_request', [], 'en'));
});

it('never ties a public request to a draft service, but staff may', function () {
    $draft = requestableService('Sample draft service', published: false);
    $data = [
        'requester_type' => 'resident', 'name' => 'Sample Resident', 'email' => 'resident@example.org',
        'category' => 'service_request', 'service_item_id' => $draft->id,
        'subject' => 'Sample subject', 'description' => 'Sample description of the request.', 'consent' => true,
    ];

    $public = app(OpenTicket::class)->handle($data, [], 'en');
    expect($public->service_item_id)->toBeNull();

    $logged = app(OpenTicket::class)->handle([...$data, 'channel' => 'phone'], [], 'en', staff(SystemRole::SuperAdmin, mfa: true));
    expect($logged->service_item_id)->toBe($draft->id);
});

it('lets the admin switch requests off and pick a form, refusing a form that is not active', function () {
    $service = requestableService();
    $form = serviceRequestForm();
    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));

    livewire(EditServiceItem::class, ['record' => $service->getRouteKey()])
        ->assertFormFieldExists('accepts_requests')
        ->assertFormFieldExists('request_form_id')
        ->fillForm(['request_form_id' => $form->id])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($service->refresh()->request_form_id)->toBe($form->id)->and($service->accepts_requests)->toBeTrue();

    $form->forceFill(['active' => false])->save();
    expect(fn () => app(SaveServiceItem::class)->handle($service, ['channels' => ['in_person'], 'request_form_id' => $form->id, 'translations' => []], staff(SystemRole::SuperAdmin, mfa: true)))
        ->toThrow(ValidationException::class);
});

it('sends a service with its own form there, and the inbox shows what the message is about', function () {
    $form = serviceRequestForm();
    $service = requestableService(data: ['request_form_id' => $form->id]);
    $slug = $service->translation('en')->slug;
    $token = SubmissionAbout::token(SubmissionAbout::SERVICE, $service->id);

    $this->get("/en/services/{$slug}")->assertOk()->assertSee('/en/forms/sample-service-request-form?about='.urlencode($token), escape: false);
    $this->get('/en/forms/sample-service-request-form?about='.urlencode($token))->assertOk()
        ->assertSee(__('support.public.requesting', [], 'en'))
        ->assertSee('name="about" value="'.$token.'"', escape: false);

    $this->post('/en/forms/sample-service-request-form', [
        'name' => 'Sample Resident', 'email' => 'resident@example.org', 'about' => $token,
        'answers' => ['detailsfld' => 'Sample details of the request'],
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
    ])->assertRedirect();

    $submission = FormSubmission::query()->sole();
    expect($submission->about_type)->toBe(SubmissionAbout::SERVICE)->and($submission->about_id)->toBe($service->id);

    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    $this->get(SubmissionResource::getUrl('view', ['record' => $submission]))->assertOk()
        ->assertSee(__('forms.about.label', [], 'en'))
        ->assertSee('Sample service to request');
});

it('drops an "about" that does not belong to the form', function () {
    $form = serviceRequestForm();
    $other = requestableService('Sample service without this form');

    $this->post('/en/forms/sample-service-request-form', [
        'name' => 'Sample Resident', 'email' => 'resident@example.org',
        'about' => SubmissionAbout::token(SubmissionAbout::SERVICE, $other->id),
        'answers' => ['detailsfld' => 'Sample details'],
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
    ])->assertRedirect();

    expect(FormSubmission::query()->sole()->about_type)->toBeNull();
    $this->get('/en/forms/sample-service-request-form?about=service.'.$other->id)->assertOk()->assertDontSee(__('support.public.requesting', [], 'en'));
});

it('falls back to the service desk when the form is not live in the reader\'s language', function () {
    $form = serviceRequestForm();
    $service = app(SaveServiceItem::class)->handle(new ServiceItem, [
        'channels' => ['in_person'], 'request_form_id' => $form->id,
        'translations' => ['en' => ['title' => 'Sample service', 'published' => true], 'am' => ['title' => 'ናሙና አገልግሎት', 'published' => true]],
    ], staff(SystemRole::ServiceOwner));

    $amSlug = $service->translation('am')->slug;
    $this->get("/am/services/{$amSlug}")->assertOk()->assertSee("/am/support/new?service={$amSlug}", escape: false);
});
