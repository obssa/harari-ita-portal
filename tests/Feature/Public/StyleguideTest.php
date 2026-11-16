<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;

it('shows the styleguide outside production', function () {
    $this->get('/styleguide')
        ->assertOk()
        ->assertSee('Styleguide')
        ->assertSee('--color-primary-600', escape: false)
        ->assertSee('<html lang="en">', escape: false);
});

it('shows the styleguide in another language for typography checks', function () {
    $this->get('/styleguide?lang=am')
        ->assertOk()
        ->assertSee('<html lang="am">', escape: false)
        ->assertSee(__('holding.title', [], 'am'));

    $this->get('/styleguide?lang=fr')->assertSee('<html lang="en">', escape: false);
});

it('hides the styleguide in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/styleguide')->assertNotFound();
});

it('renders buttons as links or buttons with the chosen variant', function () {
    expect(Blade::render('<x-ui.button href="/en" variant="secondary">Go</x-ui.button>'))
        ->toContain('<a href="/en"', 'border-line', 'rounded-control', '>Go</a>')
        ->and(Blade::render('<x-ui.button href="/en/volunteer" variant="action">Offer your skills</x-ui.button>'))
        ->toContain('bg-saffron-500', 'text-ink-brand')
        ->and(Blade::render('<x-ui.button type="submit">Send</x-ui.button>'))
        ->toContain('<button type="submit"', 'bg-primary-700');
});

it('renders badges in each tone', function (string $tone, string $class) {
    expect(Blade::render('<x-ui.badge :tone="$tone">Label</x-ui.badge>', ['tone' => $tone]))->toContain($class, 'Label');
})->with([
    ['primary', 'bg-primary-50'],
    ['cyan', 'bg-cyan-50'],
    ['neutral', 'bg-surface-muted'],
    ['success', 'bg-success-50'],
    ['warning', 'bg-warning-50'],
    ['danger', 'bg-danger-50'],
    ['saffron', 'bg-saffron-50'],
    ['ruby', 'bg-ruby-50'],
    ['ink', 'bg-night-900'],
]);

it('renders cards with an optional link, badges and meta line', function () {
    $html = Blade::render(
        '<x-ui.card title="Rally" kicker="News" href="/en/news/1" :heading-level="2">Body<x-slot:badges>B</x-slot:badges><x-slot:meta>M</x-slot:meta></x-ui.card>',
    );

    expect($html)->toContain('<h2', '<a href="/en/news/1"', 'News', 'Body', 'B', 'M')
        ->and(Blade::render('<x-ui.card title="Plain" />'))->not->toContain('<a ');
});

it('renders a date tile with the Ethiopian day and month and the Gregorian date', function () {
    app()->setLocale('en');
    $html = Blade::render('<x-ui.date-tile :date="$date" />', ['date' => CarbonImmutable::parse('2026-09-11 10:00', 'Africa/Addis_Ababa')]);

    expect($html)->toContain('datetime="2026-09-11"', '>1<', 'Meskerem', '11 September 2026');
});

it('renders an empty state with its message and next step', function () {
    $html = Blade::render('<x-ui.empty-state title="Nothing yet">Sample message<x-slot:action><a href="/en">Back</a></x-slot:action></x-ui.empty-state>');

    expect($html)->toContain('border-dashed', 'Nothing yet', 'Sample message', '<a href="/en">Back</a>');
});

it('marks the current step and tells screen readers which steps are done', function () {
    $html = Blade::render('<x-ui.stepper :steps="[\'One\', \'Two\', \'Three\']" :current="1" />');

    expect($html)->toContain('aria-current="step"', __('ui.step_done'), '03')
        ->and(substr_count($html, 'aria-current="step"'))->toBe(1);
});

it('gives alerts the right role and status pills a dot', function () {
    expect(Blade::render('<x-ui.alert tone="danger">Bad</x-ui.alert>'))->toContain('role="alert"', 'border-danger-600')
        ->and(Blade::render('<x-ui.alert>Note</x-ui.alert>'))->toContain('role="status"')
        ->and(Blade::render('<x-ui.status-pill state="down">Outage</x-ui.status-pill>'))->toContain('bg-danger-600', 'animate-ping', 'Outage')
        ->and(Blade::render('<x-ui.status-pill>Fine</x-ui.status-pill>'))->not->toContain('animate-ping');
});

it('shows a figure with its source and escapes code samples', function () {
    expect(Blade::render('<x-ui.kpi label="Sample" value="12" source="Source: sample" />'))->toContain('tabular-nums', 'Source: sample')
        ->and(Blade::render('<x-ui.code label="x">{{ $snippet }}</x-ui.code>', ['snippet' => '<b>1</b>']))->toContain('&lt;b&gt;1&lt;/b&gt;');
});
