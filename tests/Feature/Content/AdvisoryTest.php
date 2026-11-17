<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\SavePost;
use App\Domain\Content\Enums\AdvisorySeverity;
use App\Domain\Content\Models\Post;
use App\Domain\Engagement\Actions\DraftCampaign;
use App\Domain\Engagement\Actions\SendCampaign;
use App\Domain\Engagement\Models\Campaign;
use App\Domain\Engagement\Models\CampaignDelivery;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Opportunities\Actions\SaveOpportunity;
use App\Domain\Opportunities\Models\Opportunity;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Livewire\livewire;

/**
 * A published sample advisory in English and Amharic, saved by a Communications Editor.
 *
 * @param  array<string, mixed>  $data
 */
function sampleAdvisory(string $severity = 'warning', array $data = [], ?User $actor = null): Post
{
    return app(SavePost::class)->handle(new Post, [
        'type' => 'advisory', 'severity' => $severity,
        'translations' => [
            'en' => ['title' => 'Sample phishing advisory', 'excerpt' => 'Sample: fake password pages.', 'affected' => "Sample mail accounts\nSample help desk",
                'actions' => "Sample step one\nSample step two", 'status' => 'published'],
            'am' => ['title' => 'የናሙና የማጭበርበሪያ ምክር', 'excerpt' => 'ናሙና።', 'actions' => 'ናሙና እርምጃ', 'status' => 'published'],
        ],
        ...$data,
    ], $actor ?? staff(SystemRole::CommunicationsEditor));
}

it('makes advisories only for those who manage them, always with a severity', function () {
    $post = sampleAdvisory();
    expect($post->isAdvisory())->toBeTrue()->and($post->severity)->toBe(AdvisorySeverity::Warning)
        ->and($post->translation('en')?->actionList())->toBe(['Sample step one', 'Sample step two']);

    expect(fn () => sampleAdvisory(actor: staff(SystemRole::Editor)))->toThrow(ValidationException::class);
    expect(fn () => sampleAdvisory(data: ['severity' => null]))->toThrow(ValidationException::class);

    // A news post keeps no severity or advisory fields.
    $news = app(SavePost::class)->handle(new Post, ['type' => 'news', 'severity' => 'warning', 'translations' => ['en' => ['title' => 'Sample news', 'actions' => 'Sample', 'status' => 'published']]], staff(SystemRole::CommunicationsEditor));
    expect($news->severity)->toBeNull()->and($news->translation('en')?->actions)->toBeNull();
});

it('publishes advisories in their own section with the steps and where to report, apart from the news', function () {
    sampleAdvisory();
    sampleAdvisory('info', ['translations' => ['en' => ['title' => 'Sample backup reminder', 'status' => 'published']]]);
    app(SavePost::class)->handle(new Post, ['type' => 'news', 'translations' => ['en' => ['title' => 'Sample ordinary news', 'status' => 'published']]], staff(SystemRole::CommunicationsEditor));

    $this->get('/en/advisories')->assertOk()->assertSee('Sample phishing advisory')->assertSee('Sample backup reminder')->assertDontSee('Sample ordinary news');
    $this->get('/en/advisories?severity=info')->assertOk()->assertSee('Sample backup reminder')->assertDontSee('Sample phishing advisory');

    $this->get('/en/advisories/sample-phishing-advisory')
        ->assertOk()
        ->assertSee(AdvisorySeverity::Warning->label('en'))
        ->assertSee('Sample mail accounts')
        ->assertSeeInOrder(['Sample step one', 'Sample step two'])
        ->assertSee(config('portal.advisories.incident_url'), escape: false)
        ->assertSee('"@type":"Article"', escape: false);

    $this->get('/en/news')->assertOk()->assertSee('Sample ordinary news')->assertDontSee('Sample phishing advisory');
    $this->get('/en/news/sample-phishing-advisory')->assertStatus(301)->assertRedirect('/en/advisories/sample-phishing-advisory');
    $this->get('/en/news?type=advisory')->assertStatus(301)->assertRedirect('/en/advisories');
    $this->get('/en/news/sample-ordinary-news')->assertOk();
    $this->get('/en/advisories/sample-ordinary-news')->assertNotFound();

    $this->get('/en/advisories/feed.xml')->assertOk()->assertSee('Sample phishing advisory')->assertDontSee('Sample ordinary news');
    $this->get('/en/feed.xml')->assertOk()->assertSee('/en/advisories/sample-phishing-advisory', escape: false);
});

it('drafts an email campaign from an advisory and sends it to subscribers of advisories', function () {
    Queue::fake();
    $editor = staff(SystemRole::CommunicationsEditor);
    $post = sampleAdvisory(actor: $editor);

    $campaign = app(DraftCampaign::class)->handle($post, $editor);
    expect($campaign->channel)->toBe(Campaign::EMAIL)
        ->and($campaign->audience['topics'])->toBe(['advisories'])
        ->and($campaign->languages())->toBe(['en', 'am'])
        ->and($campaign->content['en']['subject'])->toBe(AdvisorySeverity::Warning->label('en').': Sample phishing advisory')
        ->and($campaign->content['en']['body'])->toContain('1. Sample step one')->toContain('/en/advisories/sample-phishing-advisory');

    $subscribe = function (array $topics, string $email): void {
        $subscriber = new Subscriber(['locale' => 'en', 'channels' => ['email'], 'topics' => $topics, 'source' => 'form']);
        $subscriber->issueToken();
        $subscriber->forceFill(['email' => $email, 'email_hash' => Subscriber::hash($email), 'email_confirmed_at' => now()])->save();
    };
    $subscribe(['advisories'], 'sample.reader1@example.org');
    $subscribe(['news'], 'sample.reader2@example.org');

    expect(app(SendCampaign::class)->start($campaign, $editor))->toBe(1)
        ->and(CampaignDelivery::query()->where('campaign_id', $campaign->id)->count())->toBe(1);

    // Only published languages go in; nothing to send without one; only those who manage campaigns may draft.
    $draft = app(SavePost::class)->handle(new Post, ['type' => 'advisory', 'severity' => 'info', 'translations' => ['en' => ['title' => 'Sample draft advisory', 'status' => 'draft']]], $editor);
    expect(fn () => app(DraftCampaign::class)->handle($draft, $editor))->toThrow(ValidationException::class);
    expect(fn () => app(DraftCampaign::class)->handle($post, staff(SystemRole::Editor)))->toThrow(HttpException::class);
});

it('drafts a campaign from an opportunity for subscribers of opportunities, and from the admin', function () {
    $editor = staff(SystemRole::CommunicationsEditor);
    $tender = app(SaveOpportunity::class)->handle(new Opportunity, ['type' => 'tender', 'reference' => 'SAMPLE/T/5', 'closes_at' => now()->addWeek()->toDateTimeString(),
        'translations' => ['en' => ['title' => 'Sample campaign tender', 'summary' => 'Sample summary.', 'published' => true]]], staff(SystemRole::ProcurementOfficer));

    $campaign = app(DraftCampaign::class)->handle($tender, $editor);
    expect($campaign->audience['topics'])->toBe(['opportunities'])
        ->and($campaign->content['en']['subject'])->toBe('Tender: Sample campaign tender')
        ->and($campaign->content['en']['body'])->toContain('SAMPLE/T/5')->toContain('/en/opportunities/sample-campaign-tender');

    $post = sampleAdvisory(actor: $editor);
    $this->actingAs($editor);
    livewire(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertOk()
        ->assertFormSet(['severity' => 'warning'])
        ->callAction('draftCampaign')
        ->assertHasNoActionErrors();
    expect(Campaign::query()->where('name', 'like', '%Sample phishing advisory%')->count())->toBe(1);
});
