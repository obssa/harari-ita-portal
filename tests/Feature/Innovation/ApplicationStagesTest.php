<?php

use App\Domain\Access\Enums\SystemRole;
use App\Domain\Innovation\Actions\ReviewApplication;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Models\Application;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

/*
 * An application's stages (ADR-0021, ADR-0054): open stages move freely, a waitlisted one can still be accepted or
 * declined, and Accepted, Declined and Withdrawn are final. Each move is decided on the locked row.
 */

beforeEach(fn () => Mail::fake());

it('knows which stages follow which', function () {
    expect(ApplicationStage::Received->canMoveTo(ApplicationStage::Interview))->toBeTrue()
        ->and(ApplicationStage::Interview->canMoveTo(ApplicationStage::Screening))->toBeTrue()
        ->and(ApplicationStage::Waitlisted->canMoveTo(ApplicationStage::Accepted))->toBeTrue()
        ->and(ApplicationStage::Waitlisted->canMoveTo(ApplicationStage::Interview))->toBeFalse()
        ->and(ApplicationStage::Received->canMoveTo(ApplicationStage::Withdrawn))->toBeFalse();
    foreach ([ApplicationStage::Accepted, ApplicationStage::Declined, ApplicationStage::Withdrawn] as $final) {
        expect($final->nextStages())->toBe([]);
    }
});

it('never lets a stale screen undo an applicant\'s withdrawal', function () {
    $application = messagedApplication();
    $stale = Application::query()->findOrFail($application->id);
    app(ReviewApplication::class)->withdraw($application);

    expect(fn () => app(ReviewApplication::class)->move($stale, ApplicationStage::Accepted, 'Sample: welcome.', staff(SystemRole::InnovationOfficer)))
        ->toThrow(ValidationException::class);
    expect(fn () => app(ReviewApplication::class)->score($stale, array_fill_keys(Application::CRITERIA, 4), staff(SystemRole::InnovationOfficer)))
        ->toThrow(ValidationException::class);
    expect($application->refresh()->stage)->toBe(ApplicationStage::Withdrawn)
        ->and($application->decided_at)->toBeNull()
        ->and($application->score)->toBeNull();
});

it('keeps a decision final and refuses to withdraw once decided', function () {
    $application = messagedApplication();
    $officer = staff(SystemRole::InnovationOfficer);
    app(ReviewApplication::class)->move($application, ApplicationStage::Declined, 'Sample: not this round.', $officer);

    expect(fn () => app(ReviewApplication::class)->move($application, ApplicationStage::Screening, null, $officer))->toThrow(ValidationException::class);
    expect(fn () => app(ReviewApplication::class)->withdraw($application))->toThrow(ValidationException::class);
    expect($application->refresh()->stage)->toBe(ApplicationStage::Declined);

    // Moving to the stage it already has changes nothing and sends nothing.
    Mail::fake();
    app(ReviewApplication::class)->move($application, ApplicationStage::Declined, 'Sample: again.', $officer);
    Mail::assertNothingQueued();
});

it('accepts or declines a waitlisted application, and offers only the allowed stages in the admin', function () {
    $application = messagedApplication();
    $officer = staff(SystemRole::InnovationOfficer);
    app(ReviewApplication::class)->move($application, ApplicationStage::Waitlisted, 'Sample: on the waiting list.', $officer);
    app(ReviewApplication::class)->move($application, ApplicationStage::Accepted, 'Sample: a place opened.', $officer);
    expect($application->refresh()->stage)->toBe(ApplicationStage::Accepted);

    $this->actingAs(staff(SystemRole::SuperAdmin, mfa: true));
    livewire(ViewApplication::class, ['record' => $application->getRouteKey()])
        ->assertActionHidden('move')
        ->assertActionHidden('score');
});
