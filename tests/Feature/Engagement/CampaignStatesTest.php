<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Engagement\Actions\SendCampaign;
use App\Domain\Engagement\Enums\CampaignStatus;
use App\Domain\Engagement\Jobs\DeliverCampaign;
use App\Domain\Engagement\Mail\CampaignMail;
use App\Domain\Engagement\Models\Campaign;
use App\Domain\Engagement\Models\CampaignDelivery;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/*
 * A campaign's states (ADR-0052): Draft → Scheduled → Sending → Sent, or Cancelled. Every move is checked on the
 * locked row, so a stale screen, a double click or the dispatcher cannot send twice or turn a cancel into "Sent".
 */

beforeEach(fn () => Mail::fake());

it('knows which moves a campaign may make, with Sent and Cancelled final', function () {
    expect(CampaignStatus::Draft->canMoveTo(CampaignStatus::Sending))->toBeTrue()
        ->and(CampaignStatus::Scheduled->canMoveTo(CampaignStatus::Draft))->toBeTrue()
        ->and(CampaignStatus::Sending->canMoveTo(CampaignStatus::Cancelled))->toBeTrue()
        ->and(CampaignStatus::Draft->canMoveTo(CampaignStatus::Sent))->toBeFalse()
        ->and(CampaignStatus::Sending->canMoveTo(CampaignStatus::Draft))->toBeFalse();
    foreach (CampaignStatus::cases() as $to) {
        expect(CampaignStatus::Sent->canMoveTo($to))->toBeFalse()
            ->and(CampaignStatus::Cancelled->canMoveTo($to))->toBeFalse();
    }
});

it('starts a campaign once, even from a stale screen', function () {
    Queue::fake();
    $sender = staff(SystemRole::Admin);
    campaignSubscriber('en', ['news'], 'one@example.org');
    campaignSubscriber('en', ['news'], 'two@example.org');
    $campaign = emailCampaign($sender);
    $stale = Campaign::query()->findOrFail($campaign->id);

    expect(app(SendCampaign::class)->start($campaign, $sender))->toBe(2);
    expect(fn () => app(SendCampaign::class)->start($stale, $sender))->toThrow(ValidationException::class);
    expect(fn () => app(SendCampaign::class)->schedule($stale, now()->addDay()->toImmutable(), $sender))->toThrow(ValidationException::class);

    expect($campaign->deliveries()->count())->toBe(2);
    Queue::assertPushed(DeliverCampaign::class, 1);
});

it('keeps a cancel a cancel: nothing more is sent and the campaign never turns into Sent', function () {
    Queue::fake();
    $sender = staff(SystemRole::Admin);
    campaignSubscriber('en', ['news'], 'one@example.org');
    campaignSubscriber('en', ['news'], 'two@example.org');
    $campaign = emailCampaign($sender);
    app(SendCampaign::class)->start($campaign, $sender);

    // A delivery job has already loaded its batch when the cancel lands.
    $batch = $campaign->deliveries()->get();
    app(SendCampaign::class)->cancel(Campaign::query()->findOrFail($campaign->id), $sender);
    foreach ($batch as $delivery) {
        app(SendCampaign::class)->deliver($delivery);
    }
    app(SendCampaign::class)->finish($campaign->id);
    (new DeliverCampaign($campaign->id))->handle(app(SendCampaign::class));

    Mail::assertNothingSent();
    expect($campaign->refresh()->status)->toBe(CampaignStatus::Cancelled)
        ->and($campaign->deliveries()->where('status', CampaignDelivery::SKIPPED)->count())->toBe(2);

    // Cancelling again changes nothing and logs nothing.
    $logged = Activity::query()->where('event', 'cancelled')->count();
    app(SendCampaign::class)->cancel($campaign, $sender);
    expect(Activity::query()->where('event', 'cancelled')->count())->toBe($logged);
});

it('sends each delivery once when two delivery jobs run side by side', function () {
    Queue::fake();
    $sender = staff(SystemRole::Admin);
    campaignSubscriber('en', ['news'], 'one@example.org');
    $campaign = emailCampaign($sender);
    app(SendCampaign::class)->start($campaign, $sender);

    $delivery = $campaign->deliveries()->sole();
    $twin = CampaignDelivery::query()->findOrFail($delivery->id);
    app(SendCampaign::class)->deliver($delivery);
    app(SendCampaign::class)->deliver($twin);
    app(SendCampaign::class)->finish($campaign->id);

    Mail::assertSent(CampaignMail::class, 1);
    expect($delivery->refresh()->status)->toBe(CampaignDelivery::SENT)
        ->and($campaign->refresh()->status)->toBe(CampaignStatus::Sent);
});
