<?php

/*
| PLAN.md §11 (WCAG 2.2 AA), ADR-0042: axe checks the main public pages in a
| real browser with the built styles, so colour contrast is checked too.
| Serious and critical issues fail the build.
*/

use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\CourseCohort;

require_once __DIR__.'/BrowserSupport.php';

beforeEach(function () {
    $this->withVite();
    browserContent();
});

it('has no serious accessibility issues on the main pages', function () {
    // One test for all pages: setting up the database is the slow part of a browser test.
    $pages = [
        '/en', '/am', '/en/news', '/en/news/sample-browser-story', '/am/news/yenamuna-yeasash-zena',
        '/en/events', '/en/events/sample-browser-forum', '/en/events/sample-browser-forum/register',
        '/en/about/offices', '/en/about/offices/sample-browser-office', '/en/documents', '/en/search?q=sample',
        '/en/contact', '/en/social', '/en/press', '/en/about/structure', '/en/get-involved', '/om/volunteer', '/om/newsletter',
        '/en/services', '/en/status', '/en/support/new', '/en/innovation/startups', '/en/innovation/startups/apply', '/en/innovation/programmes',
        '/en/innovation/challenges', '/en/innovation/challenges/sample-browser-hackathon', '/en/innovation/challenges/sample-browser-hackathon/register', '/en/innovation/teams',
        '/en/projects', '/en/projects/sample-browser-project', '/en/data', '/en/data/sample-browser-data', '/en/numbers', '/en/developers',
        '/en/academy', '/am/academy', '/en/academy/courses/sample-digital-skills-for-office-staff', '/en/academy/certificates/verify',
        '/en/opportunities', '/am/opportunities', '/en/opportunities/sample-supply-of-network-equipment-for-regional-offices',
        '/en/advisories', '/en/advisories/sample-phishing-messages-pretending-to-be-the-agency',
    ];
    $open = CourseCohort::query()->whereDate('starts_on', '>', now()->toDateString())->firstOrFail();
    $pages[] = "/en/academy/courses/sample-digital-skills-for-office-staff/enrol/{$open->id}";
    $pages[] = Certificate::query()->firstOrFail()->verifyUrl('en');

    foreach ($pages as $path) {
        visit($path)
            ->assertNoJavaScriptErrors()
            ->assertNoAccessibilityIssues();
    }
});

it('can be used with the keyboard: the skip link leads to the content', function () {
    visit('/en')
        ->keys('html > body', 'Tab')
        ->keys('html > body', 'Enter')
        ->assertFragmentIs('content');
});
