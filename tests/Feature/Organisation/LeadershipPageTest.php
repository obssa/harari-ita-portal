<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Organisation\Actions\SaveLeader;
use App\Domain\Organisation\Enums\LeaderBody;
use App\Domain\Organisation\Models\Leader;
use App\Support\Initials;

function samplePerson(string $body, string $name, string $position, int $rank = 10): Leader
{
    return app(SaveLeader::class)->handle(new Leader, ['body' => $body, 'rank' => $rank, 'translations' => [
        'en' => ['name' => $name, 'position' => $position, 'summary' => 'Sample summary.', 'published' => true],
    ]], staff(SystemRole::Publisher, mfa: false));
}

it('lists management first, then the directors and the pillar leads, with the head of the Agency featured', function () {
    samplePerson('pillars', 'Sample Pillar Lead', 'Sample lead of a pillar');
    samplePerson('directors', 'Sample Director', 'Sample director of a directorate');
    samplePerson('management', 'Sample Deputy', 'Sample deputy head', 2);
    samplePerson('management', 'Sample Agency Head', 'Sample head of the Agency', 1);

    $html = (string) $this->get('/en/about/leadership')->assertOk()->getContent();

    $management = strpos($html, 'id="body-management"');
    $directors = strpos($html, 'id="body-directors"');
    $pillars = strpos($html, 'id="body-pillars"');
    $featured = strpos($html, __('leaders.featured'));
    expect($management)->toBeInt()->toBeLessThan($directors)
        ->and($directors)->toBeLessThan($pillars)
        ->and($html)->toContain('href="#body-management"', e(LeaderBody::Management->description('en')))
        ->and($featured)->toBeInt()->toBeLessThan($management)
        ->and(strpos($html, 'Sample Agency Head'))->toBeLessThan($management);
});

it('draws initials without honorifics for people without a photo', function () {
    expect(Initials::of('Sample Person'))->toBe('SP')
        ->and(Initials::of('አቶ ናሙና ሰው'))->toBe('ናሰ')
        ->and(Initials::of('H.E Sample Person Name'))->toBe('SP')
        ->and(Initials::of('ክቡር አቶ ናሙና ሰው ስም'))->toBe('ናሰ')
        ->and(Initials::of('Kabajamoo Obbo Fakkii Namaa Maqaa'))->toBe('FN')
        ->and(Initials::of('Obbo Fakkii Namaa'))->toBe('FN')
        ->and(Initials::of('Sample'))->toBe('S');
});
