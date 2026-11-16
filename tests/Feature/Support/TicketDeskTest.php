<?php

use App\Domain\Access\Enums\ScopeType;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Organisation\Actions\SaveOffice;
use App\Domain\Organisation\Models\Office;
use App\Domain\Support\Actions\OpenTicket;
use App\Domain\Support\Actions\ReplyToTicket;
use App\Domain\Support\Actions\RequesterReply;
use App\Domain\Support\Actions\UpdateTicket;
use App\Domain\Support\Enums\SlaState;
use App\Domain\Support\Enums\TicketCategory;
use App\Domain\Support\Enums\TicketPriority;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Mail\TicketReceivedMail;
use App\Domain\Support\Mail\TicketUpdatedMail;
use App\Domain\Support\Models\SlaPolicy;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Sla\WorkingHours;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

function deskOffice(string $name = 'Sample ICT directorate'): Office
{
    return app(SaveOffice::class)->handle(new Office, ['type' => 'directorate', 'translations' => ['en' => ['name' => $name, 'published' => true]]], staff(SystemRole::CommunicationsEditor));
}

/**
 * @param  array<string, mixed>  $data
 */
function sampleTicket(array $data = [], ?User $staff = null): Ticket
{
    return app(OpenTicket::class)->handle([
        'requester_type' => 'resident',
        'name' => 'Sample Requester',
        'email' => 'requester@example.org',
        'category' => 'network',
        'subject' => 'Sample: the internet is down',
        'description' => 'Sample description of the problem in the office.',
        'consent' => true,
        ...$data,
    ], [], 'en', $staff);
}

it('counts SLA targets in working hours, Monday to Friday, in Addis Ababa', function () {
    $tz = 'Africa/Addis_Ababa';
    // Friday 16:30 + 2 working hours: one hour on Friday, one on Monday from 08:30.
    $friday = CarbonImmutable::parse('2026-11-20 16:30', $tz);
    expect(WorkingHours::add($friday, 2)->setTimezone($tz)->format('D H:i'))->toBe('Mon 09:30');

    // Saturday counts from Monday's opening.
    expect(WorkingHours::add(CarbonImmutable::parse('2026-11-21 10:00', $tz), 1)->setTimezone($tz)->format('D H:i'))->toBe('Mon 09:30');

    // A full working day is nine hours.
    expect(WorkingHours::add(CarbonImmutable::parse('2026-11-16 08:30', $tz), 9)->setTimezone($tz)->format('D H:i'))->toBe('Mon 17:30')
        ->and(WorkingHours::minutesBetween(CarbonImmutable::parse('2026-11-20 16:30', $tz), CarbonImmutable::parse('2026-11-23 09:30', $tz)))->toBe(120);
});

it('uses the most specific SLA policy, then the defaults', function () {
    expect(SlaPolicy::targets(TicketCategory::Network, TicketPriority::Urgent))->toBe(['response' => 1, 'resolve' => 8]);

    SlaPolicy::query()->create(['priority' => 'urgent', 'category' => null, 'response_hours' => 2, 'resolve_hours' => 10]);
    SlaPolicy::query()->create(['priority' => 'urgent', 'category' => 'network', 'response_hours' => 1, 'resolve_hours' => 4]);

    expect(SlaPolicy::targets(TicketCategory::Network, TicketPriority::Urgent))->toBe(['response' => 1, 'resolve' => 4])
        ->and(SlaPolicy::targets(TicketCategory::Account, TicketPriority::Urgent))->toBe(['response' => 2, 'resolve' => 10]);
});

it('opens a ticket with a reference, encrypted details, SLA targets and a receipt', function () {
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00', 'Africa/Addis_Ababa'));

    $ticket = sampleTicket();

    expect($ticket->reference)->toMatch('/^'.Ticket::REFERENCE_PATTERN.'$/')
        ->and($ticket->status)->toBe(TicketStatus::New)
        ->and($ticket->priority)->toBe(TicketPriority::Normal)
        ->and($ticket->response_due_at?->setTimezone('Africa/Addis_Ababa')->format('D H:i'))->toBe('Mon 17:00')
        ->and($ticket->slaState())->toBe(SlaState::OnTime);

    $raw = DB::table('tickets')->where('id', $ticket->id)->first();
    expect($raw->email)->not->toContain('requester@example.org')
        ->and($raw->description)->not->toContain('Sample description');

    Mail::assertQueued(TicketReceivedMail::class, fn (TicketReceivedMail $mail): bool => $mail->hasTo('requester@example.org'));
    expect(Ticket::normaliseReference(strtolower(str_replace('-', ' ', $ticket->reference))))->toBe($ticket->reference)
        ->and(Ticket::normaliseReference('SUP-0000-0000'))->toBe('');
});

it('refuses a ticket without a way to answer, a consent or a proper description', function () {
    expect(fn () => sampleTicket(['email' => '', 'phone' => '']))->toThrow(ValidationException::class)
        ->and(fn () => sampleTicket(['consent' => false]))->toThrow(ValidationException::class)
        ->and(fn () => sampleTicket(['description' => 'short']))->toThrow(ValidationException::class)
        ->and(fn () => sampleTicket(['requester_type' => 'public_office', 'requester_office' => '']))->toThrow(ValidationException::class);
});

it('stops the response clock on the first reply, resolves, reopens on the requester\'s reply and closes with a rating', function () {
    Mail::fake();
    $agent = staff(SystemRole::ServiceDeskAgent);
    $ticket = sampleTicket();

    app(ReplyToTicket::class)->handle($ticket, $agent, 'Sample internal note', internal: true);
    expect($ticket->refresh()->first_response_at)->toBeNull();

    app(ReplyToTicket::class)->handle($ticket, $agent, 'Sample answer: please restart the router.', waitForRequester: true);
    $ticket->refresh();
    expect($ticket->first_response_at)->not->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::Waiting)
        ->and($ticket->assignee_id)->toBe($agent->id)
        ->and($ticket->responseState())->toBe(SlaState::Met);
    Mail::assertQueued(TicketUpdatedMail::class, fn (TicketUpdatedMail $mail): bool => $mail->kind === 'reply');

    app(RequesterReply::class)->message($ticket, 'Sample: it still does not work.');
    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress);

    app(UpdateTicket::class)->handle($ticket, ['status' => 'resolved'], $agent);
    expect($ticket->refresh()->status)->toBe(TicketStatus::Resolved)->and($ticket->resolved_at)->not->toBeNull();
    Mail::assertQueued(TicketUpdatedMail::class, fn (TicketUpdatedMail $mail): bool => $mail->kind === 'resolved');

    app(RequesterReply::class)->rate($ticket, 5, 'Sample thanks');
    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Closed)
        ->and($ticket->satisfaction)->toBe(5)
        ->and($ticket->slaState())->toBe(SlaState::Met)
        ->and(fn () => app(RequesterReply::class)->message($ticket, 'Sample late message'))->toThrow(ValidationException::class);
});

it('moves the targets when the priority changes and marks an overdue ticket', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00', 'Africa/Addis_Ababa'));
    $ticket = sampleTicket();
    app(UpdateTicket::class)->handle($ticket, ['priority' => 'urgent'], staff(SystemRole::ServiceDeskAgent));

    expect($ticket->refresh()->resolve_due_at?->setTimezone('Africa/Addis_Ababa')->format('D H:i'))->toBe('Mon 17:00');

    $this->travelTo(CarbonImmutable::parse('2026-11-16 15:30', 'Africa/Addis_Ababa'));
    expect($ticket->slaState())->toBe(SlaState::DueSoon);
    $this->travelTo(CarbonImmutable::parse('2026-11-17 09:00', 'Africa/Addis_Ababa'));
    expect($ticket->slaState())->toBe(SlaState::Breached);
});

it('keeps an agent scoped to a directorate to that directorate\'s tickets', function () {
    $own = deskOffice('Sample own directorate');
    $other = deskOffice('Sample other directorate');
    $agent = User::factory()->create();
    $agent->assignScopedRole(SystemRole::ServiceDeskAgent, ScopeType::Office, $own->id);

    $outsider = User::factory()->create();
    $outsider->assignScopedRole(SystemRole::ServiceDeskAgent, ScopeType::Office, $other->id);

    $mine = sampleTicket(['office_id' => $own->id], staff(SystemRole::Admin));
    $theirs = sampleTicket(['office_id' => $other->id], staff(SystemRole::Admin));

    expect($agent->can('view', $mine))->toBeTrue()
        ->and($agent->can('view', $theirs))->toBeFalse()
        ->and($agent->can('reply', $theirs))->toBeFalse()
        ->and(fn () => app(UpdateTicket::class)->handle($mine, ['assignee_id' => $outsider->id], $agent))->toThrow(ValidationException::class);
});

it('lists, records and answers tickets in the admin, and keeps them from other staff', function () {
    Mail::fake();
    $ticket = sampleTicket();

    $this->actingAs(staff(SystemRole::Author))->get('/admin/tickets')->assertForbidden();
    $this->actingAs(staff(SystemRole::ServiceDeskAgent));
    $this->get('/admin/tickets')->assertOk()->assertSee($ticket->reference);
    $this->get('/admin/tickets/board')->assertOk()->assertSee($ticket->reference)->assertSee('Sample: the internet is down');
    $this->get('/admin/tickets/'.$ticket->id)->assertOk()->assertSee('Sample description of the problem in the office.')->assertSee('requester@example.org');

    livewire(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('reply', data: ['body' => 'Sample answer from the desk', 'wait' => false])
        ->assertHasNoActionErrors();
    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress);

    livewire(CreateTicket::class)
        ->fillForm(['channel' => 'phone', 'requester_type' => 'public_office', 'requester_office' => 'Sample bureau', 'name' => 'Sample Caller', 'phone' => '0911000000', 'category' => 'hardware', 'priority' => 'high', 'subject' => 'Sample printer', 'description' => 'Sample printer does not print.', 'locale' => 'am'])
        ->call('create')
        ->assertHasNoFormErrors();
    $recorded = Ticket::query()->where('channel', 'phone')->firstOrFail();
    expect($recorded->priority)->toBe(TicketPriority::High)->and($recorded->locale)->toBe('am');
});
