<?php

use App\Domain\Academy\Models\Course;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Content\Actions\LoadContentPack;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Homepage\Queries\PortalPulse;
use App\Domain\Projects\Models\Statistic;
use Illuminate\Support\Facades\Blade;

/*
 * ADR-0051: the homepage's live panel, published figures, roadmap and moving circuit. Every number on them comes
 * from the database, and the motion is an extra on markup that already reads complete.
 */

beforeEach(function () {
    staff(SystemRole::SuperAdmin, mfa: false);
    app(LoadContentPack::class)->handle(database_path('content/pack.json'));
});

it('counts only what is published, shows nothing at zero and at most four figures', function () {
    $pulse = collect(PortalPulse::for('en'))->keyBy('key');
    expect($pulse->count())->toBeLessThanOrEqual(PortalPulse::MAX)
        ->and($pulse->every(fn (array $figure): bool => $figure['value'] > 0))->toBeTrue()
        ->and($pulse->get('courses')['value'] ?? null)->toBe(6)
        ->and($pulse->get('courses')['label'] ?? null)->toBe('courses');

    Course::query()->firstOrFail()->translations()->update(['status' => ContentStatus::Draft->value]);
    expect(collect(PortalPulse::for('en'))->keyBy('key')->get('courses')['value'] ?? null)->toBe(5);
});

it('opens with the live panel, follows the pillars with the sourced figures and shows the roadmap', function () {
    $home = (string) $this->get('/en')->assertOk()->getContent();

    expect($home)->toContain(__('homepage.pulse.kicker', [], 'en'))
        ->toContain('data-count-to="6"')
        ->toContain(__('homepage.impact.title', [], 'en'))
        ->toContain('55,000')
        ->toContain('https://www.ena.et/web/amh/w/amh_9899183')
        ->toContain(__('homepage.projects.stage', ['stage' => __('projects.status.planned', [], 'en')], 'en'))
        ->toContain('class="circuit-flow"')
        ->toContain('data-motion');
    // The figures come straight after the pillars.
    expect(strpos($home, 'id="home-impact"'))->toBeGreaterThan(strpos($home, 'id="home-pillars"'))
        ->and(strpos($home, 'id="home-impact"'))->toBeLessThan(strpos($home, 'id="home-services"') ?: PHP_INT_MAX);

    $this->get('/am')->assertOk()->assertSee(__('homepage.impact.title', [], 'am'))->assertSee('ኢዜአ');
    $this->get('/om')->assertOk()->assertSee(__('homepage.pulse.kicker', [], 'om'));
});

it('hides the figures band when no figure is published for the homepage', function () {
    Statistic::query()->update(['on_achievements' => false]);

    expect((string) $this->get('/en')->assertOk()->getContent())->not->toContain('id="home-impact"');
});

it('draws light along the circuit only when asked, and turns each story tile its own way', function () {
    expect(Blade::render('<x-circuit />'))->not->toContain('circuit-flow')
        ->and(Blade::render('<x-circuit :flow="true" />'))->toContain('circuit-flow')->toContain('pathLength="100"');

    $tiles = collect(['a', 'b', 'c', 'd', 'e', 'f'])->map(fn (string $seed): string => Blade::render('<x-news-placeholder :seed="$seed" />', ['seed' => $seed]));
    expect($tiles->unique()->count())->toBeGreaterThan(1);
});
