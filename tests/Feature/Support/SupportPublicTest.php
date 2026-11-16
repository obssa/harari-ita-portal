<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Support\Actions\ReplyToTicket;
use App\Domain\Support\Actions\UpdateTicket;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Support\SpamGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TestFiles;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function supportForm(array $overrides = []): array
{
    return [
        'requester_type' => 'public_office',
        'requester_office' => 'Sample woreda office',
        'name' => 'Sample Officer',
        'email' => 'officer@example.org',
        'phone' => '0911223344',
        'category' => 'network',
        'subject' => 'Sample: no network in the office',
        'description' => 'Sample: since this morning no computer in the office reaches the network.',
        'consent' => '1',
        SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10)),
        ...$overrides,
    ];
}

it('shows the request form in every language and the menu links to it', function () {
    foreach (['en', 'am', 'om'] as $locale) {
        $this->get("/{$locale}/support/new")->assertOk()->assertSee(__('support.public.title', [], $locale));
    }
    $this->get('/en')->assertOk()->assertSee('/en/support/new', escape: false);
});

it('files a request with an attachment and opens it straight away for the sender', function () {
    Mail::fake();
    Storage::fake('local');

    $response = $this->post('/en/support/new', supportForm(['files' => [new UploadedFile(TestFiles::image(400, 300, 'png'), 'screen.png', 'image/png', null, true)]]));
    $ticket = Ticket::query()->firstOrFail();

    $response->assertRedirect('/en/support/track/'.$ticket->reference);
    expect($ticket->files)->toHaveCount(1)
        ->and($ticket->files[0]['mime'])->toBe('image/png')
        ->and($ticket->requester_office)->toBe('Sample woreda office');
    Storage::disk('local')->assertExists($ticket->files[0]['path']);

    $this->get('/en/support/track/'.$ticket->reference)
        ->assertOk()
        ->assertSee('Sample: no network in the office')
        ->assertSee(__('support.public.received', [], 'en'))
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->get('/en/support/track/'.$ticket->reference.'/files/t0')->assertOk();
});

it('refuses a request without consent, contact or with the honeypot filled', function () {
    $this->post('/en/support/new', supportForm(['consent' => null]))->assertSessionHasErrors('consent');
    $this->post('/en/support/new', supportForm(['email' => '', 'phone' => '']))->assertSessionHasErrors('email');
    $this->post('/en/support/new', supportForm(['website' => 'http://spam.example']))->assertSessionHasErrors('form');
    expect(Ticket::query()->count())->toBe(0);
});

it('follows a request only with the reference and the contact given, and says which one is wrong', function () {
    Mail::fake();
    $this->post('/en/support/new', supportForm());
    $ticket = Ticket::query()->firstOrFail();
    $this->flushSession();

    // A new visitor sees only the form, never the request.
    $this->get('/en/support/track/'.$ticket->reference)->assertOk()->assertDontSee('Sample: no network in the office')->assertSee(__('support.track.contact', [], 'en'));
    $this->get('/en/support/track/'.$ticket->reference.'/files/t0')->assertForbidden();

    $this->post('/en/support/track', ['reference' => 'SUP-2222-2222', 'contact' => 'officer@example.org'])->assertSessionHasErrors(['reference' => __('support.track.not_found', [], 'en')]);
    $this->post('/en/support/track', ['reference' => $ticket->reference, 'contact' => 'someone@example.org'])->assertSessionHasErrors(['contact' => __('support.track.contact_mismatch', [], 'en')]);
    $this->post('/en/support/track', ['reference' => 'nonsense', 'contact' => ''])->assertSessionHasErrors(['reference', 'contact']);

    // The phone number as people type it works too.
    $this->post('/en/support/track', ['reference' => strtolower($ticket->reference), 'contact' => '+251 911 22 33 44'])->assertRedirect('/en/support/track/'.$ticket->reference);
    $this->get('/en/support/track/'.$ticket->reference)->assertOk()->assertSee('Sample: no network in the office');
});

it('shows staff replies but never internal notes, takes the requester\'s answer and a rating', function () {
    Mail::fake();
    $this->post('/en/support/new', supportForm());
    $ticket = Ticket::query()->firstOrFail();
    $agent = staff(SystemRole::ServiceDeskAgent);

    app(ReplyToTicket::class)->handle($ticket, $agent, 'Sample note only for staff', internal: true);
    app(ReplyToTicket::class)->handle($ticket, $agent, 'Sample answer: restart the switch.', waitForRequester: true);

    $this->get('/en/support/track/'.$ticket->reference)->assertSee('Sample answer: restart the switch.')->assertDontSee('Sample note only for staff');

    $this->post('/en/support/track/'.$ticket->reference.'/reply', ['body' => 'Sample: done, it works now.', SpamGuard::STARTED => Crypt::encryptString((string) (time() - 10))])
        ->assertRedirect('/en/support/track/'.$ticket->reference);
    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress);

    app(UpdateTicket::class)->handle($ticket, ['status' => 'resolved'], $agent);
    $this->get('/en/support/track/'.$ticket->reference)->assertSee(__('support.public.rate_title', [], 'en'));
    $this->post('/en/support/track/'.$ticket->reference.'/rate', ['satisfaction' => '4', 'note' => 'Sample quick help'])->assertRedirect();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Closed)->and($ticket->satisfaction)->toBe(4);
    $this->get('/en/support/track/'.$ticket->reference)->assertDontSee(__('support.public.reply_title', [], 'en'));
});
