<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Status\Actions\PostIncidentUpdate;
use App\Domain\Status\Actions\SaveIncident;
use App\Domain\Status\Enums\ComponentState;
use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Status\Support\StatusBoard;
use App\Filament\Resources\Incidents\Pages\CreateIncident;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

function statusSystem(string $name, int $sort = 0): StatusComponent
{
    return StatusComponent::query()->create(['names' => ['en' => $name, 'am' => 'ናሙና '.$name], 'sort' => $sort]);
}

/**
 * @param  array<string, mixed>  $data
 */
function statusIncident(array $data): Incident
{
    return app(SaveIncident::class)->handle(new Incident, [
        'titles' => ['en' => 'Sample: email is slow'],
        'impact' => 'degraded',
        'bodies' => ['en' => 'Sample: we are looking into slow email.'],
        ...$data,
    ], staff(SystemRole::ServiceOwner));
}

it('shows every system working when nothing is wrong', function () {
    statusSystem('Sample website');
    statusSystem('Sample email', 1);

    $this->get('/en/status')
        ->assertOk()
        ->assertSee(__('status.overall.operational', [], 'en'))
        ->assertSeeInOrder(['Sample website', 'Sample email'])
        ->assertSee(__('status.no_history', [], 'en'));
    $this->get('/am/status')->assertOk()->assertSee('ናሙና Sample website');
});

it('shows an incident on its systems, takes updates and moves it to the history when resolved', function () {
    $web = statusSystem('Sample website');
    $mail = statusSystem('Sample email', 1);
    $incident = statusIncident(['components' => [$mail->id], 'impact' => 'major_outage']);

    expect($incident->stage)->toBe(IncidentStage::Investigating)
        ->and((new StatusBoard)->overall())->toBe(ComponentState::MajorOutage);

    $this->get('/en/status')
        ->assertSee(__('status.overall.major_outage', [], 'en'))
        ->assertSee('Sample: email is slow')
        ->assertSee('Sample: we are looking into slow email.')
        ->assertSee(__('status.active', [], 'en'));

    $board = new StatusBoard;
    expect(collect($board->components())->firstWhere('component.id', $web->id)['state'])->toBe(ComponentState::Operational);

    app(PostIncidentUpdate::class)->handle($incident, IncidentStage::Resolved, ['en' => 'Sample: email works again.'], staff(SystemRole::ServiceOwner));
    expect($incident->refresh()->resolved_at)->not->toBeNull()->and((new StatusBoard)->overall())->toBe(ComponentState::Operational);

    $this->get('/en/status')->assertSee(__('status.overall.operational', [], 'en'))->assertSee('Sample: email works again.')->assertDontSee(__('status.active', [], 'en'));

    $json = $this->get('/en/status.json')->assertOk()->json();
    expect($json['status']['state'])->toBe('operational')
        ->and($json['components'])->toHaveCount(2)
        ->and($json['incidents'][0]['updates'][0]['body'])->toBe('Sample: email works again.');
    $this->get('/en/status.rss')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')->assertSee('Sample: email is slow');
});

it('shows planned maintenance as upcoming until it starts', function () {
    $web = statusSystem('Sample website');
    $start = CarbonImmutable::now()->addDay();
    $maintenance = statusIncident(['kind' => 'maintenance', 'impact' => 'maintenance', 'components' => [$web->id], 'started_at' => $start->toIso8601String(), 'titles' => ['en' => 'Sample: server upgrade']]);

    expect($maintenance->stage)->toBe(IncidentStage::Scheduled)
        ->and((new StatusBoard)->overall())->toBe(ComponentState::Operational);
    $this->get('/en/status')->assertSee(__('status.upcoming', [], 'en'))->assertSee('Sample: server upgrade');

    $this->travelTo($start->addMinute());
    expect((new StatusBoard)->overall())->toBe(ComponentState::Maintenance);
});

it('refuses an incident without systems, impact or first update', function () {
    statusSystem('Sample website');
    expect(fn () => statusIncident(['components' => []]))->toThrow(ValidationException::class)
        ->and(fn () => statusIncident(['components' => [StatusComponent::query()->value('id')], 'impact' => 'operational']))->toThrow(ValidationException::class)
        ->and(fn () => statusIncident(['components' => [StatusComponent::query()->value('id')], 'bodies' => []]))->toThrow(ValidationException::class);
});

it('opens and updates incidents from the admin, for staff with the permission only', function () {
    $system = statusSystem('Sample network');
    $this->actingAs(staff(SystemRole::Author))->get('/admin/incidents')->assertForbidden();
    $this->actingAs(staff(SystemRole::ServiceDeskAgent))->get('/admin/incidents')->assertOk();

    $this->actingAs(staff(SystemRole::ServiceOwner));
    $this->get('/admin/status-components')->assertOk()->assertSee('Sample network');
    livewire(CreateIncident::class)
        ->fillForm(['kind' => 'incident', 'impact' => 'partial_outage', 'components' => [$system->id], 'titles' => ['en' => 'Sample: network cut'], 'bodies' => ['en' => 'Sample: a cable was cut.']])
        ->call('create')
        ->assertHasNoFormErrors();

    $incident = Incident::query()->firstOrFail();
    livewire(EditIncident::class, ['record' => $incident->getRouteKey()])
        ->assertSee('Sample: a cable was cut.')
        ->callAction('postUpdate', data: ['stage' => 'identified', 'bodies' => ['en' => 'Sample: crew on the way.']])
        ->assertHasNoActionErrors();

    expect($incident->refresh()->stage)->toBe(IncidentStage::Identified)->and($incident->updates()->count())->toBe(2);
});

it('moves an incident only along the allowed stages, on the locked incident (ADR-0054)', function () {
    $owner = staff(SystemRole::ServiceOwner);
    expect(IncidentStage::Investigating->canMoveTo(IncidentStage::Scheduled))->toBeFalse()
        ->and(IncidentStage::Resolved->canMoveTo(IncidentStage::Scheduled))->toBeFalse()
        ->and(IncidentStage::Resolved->canMoveTo(IncidentStage::Investigating))->toBeTrue()
        ->and(IncidentStage::Scheduled->canMoveTo(IncidentStage::Scheduled))->toBeTrue();

    $incident = statusIncident(['components' => [statusSystem('Sample website')->id]]);
    expect($incident->stage)->toBe(IncidentStage::Investigating);
    $post = app(PostIncidentUpdate::class);
    expect(fn () => $post->handle($incident, IncidentStage::Scheduled, ['en' => 'Sample'], $owner))->toThrow(ValidationException::class);

    $post->handle($incident, IncidentStage::Resolved, ['en' => 'Sample: fixed.'], $owner);
    $resolvedAt = $incident->resolved_at;
    $this->travel(5)->minutes();
    // A note added after the fix keeps the time it was fixed; a stale screen cannot set it back to scheduled.
    $stale = Incident::query()->findOrFail($incident->id);
    $stale->stage = IncidentStage::Scheduled;
    $post->handle($incident, IncidentStage::Resolved, ['en' => 'Sample: a short report follows.'], $owner);
    expect($incident->refresh()->resolved_at->equalTo($resolvedAt))->toBeTrue()
        ->and(fn () => $post->handle($stale, IncidentStage::Scheduled, ['en' => 'Sample'], $owner))->toThrow(ValidationException::class);

    // Reopening goes to a working stage.
    $post->handle($incident, IncidentStage::Investigating, ['en' => 'Sample: it is back.'], $owner);
    expect($incident->refresh()->stage)->toBe(IncidentStage::Investigating)->and($incident->resolved_at)->toBeNull();
});
