<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Privacy\Actions\ErasePersonalData;
use App\Domain\Privacy\Actions\ExportPersonalData;
use App\Domain\Privacy\Support\PersonalDataSearch;
use App\Domain\Support\Actions\OpenTicket;
use App\Domain\Support\Actions\ReplyToTicket;
use App\Domain\Support\Actions\UpdateTicket;
use App\Domain\Support\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

function privacyTicket(string $email = 'person@example.org'): Ticket
{
    return app(OpenTicket::class)->handle([
        'requester_type' => 'resident',
        'name' => 'Sample Person',
        'email' => $email,
        'phone' => '0911234567',
        'category' => 'account',
        'subject' => 'Sample: cannot sign in',
        'description' => 'Sample: my account is locked since yesterday.',
        'consent' => true,
    ], [], 'en');
}

it('finds, exports and erases a person\'s support tickets', function () {
    Mail::fake();
    $ticket = privacyTicket();
    app(ReplyToTicket::class)->handle($ticket, staff(SystemRole::ServiceDeskAgent), 'Sample internal remark', internal: true);
    $search = new PersonalDataSearch('person@example.org', null);

    expect($search->counts())->toMatchArray(['tickets' => 1])
        ->and($search->erasable())->toBe(1);

    $export = app(ExportPersonalData::class)->handle($search, staff(SystemRole::Admin));
    expect($export['support_tickets'][0])->toMatchArray(['reference' => $ticket->reference, 'subject' => 'Sample: cannot sign in', 'email' => 'person@example.org'])
        ->and($export['support_tickets'][0]['messages'])->toBe([]);

    app(ErasePersonalData::class)->handle(new PersonalDataSearch('person@example.org', null), staff(SystemRole::Admin));
    $ticket->refresh();
    expect($ticket->email)->toBeNull()
        ->and($ticket->name)->toBeNull()
        ->and($ticket->description)->not->toContain('locked')
        ->and($ticket->erased_at)->not->toBeNull()
        ->and((new PersonalDataSearch('person@example.org', null))->counts()['tickets'])->toBe(0);

    // An erased ticket can no longer be opened from the tracking page.
    $this->post('/en/support/track', ['reference' => $ticket->reference, 'contact' => 'person@example.org'])->assertSessionHasErrors('reference');
});

it('deletes closed tickets after the retention period and keeps open ones', function () {
    Mail::fake();
    $agent = staff(SystemRole::ServiceDeskAgent);
    $old = privacyTicket('old@example.org');
    $open = privacyTicket('open@example.org');
    app(UpdateTicket::class)->handle($old, ['status' => 'closed'], $agent);

    $this->travelTo(CarbonImmutable::now()->addDays((int) config('portal.support.retention_days') + 1));
    $this->artisan('support:purge-tickets')->assertSuccessful();

    expect(Ticket::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(Ticket::query()->whereKey($open->id)->exists())->toBeTrue();
});
