<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Enums\RiskLevel;
use App\Domain\Projects\Enums\RiskStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectRisk;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Adds or changes a project risk (ADR-0024). Risks are for staff only; the owner must be a member of staff who can
 * see the project.
 */
final class SaveRisk
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, ProjectRisk $risk, array $data, User $actor): ProjectRisk
    {
        if (! $actor->can('update', $project)) {
            throw new AuthorizationException;
        }

        $title = trim((string) ($data['title'] ?? ''));
        $likelihood = RiskLevel::tryFrom((string) ($data['likelihood'] ?? ''));
        $impact = RiskLevel::tryFrom((string) ($data['impact'] ?? ''));
        $status = RiskStatus::tryFrom((string) ($data['status'] ?? RiskStatus::Open->value)) ?? RiskStatus::Open;

        $errors = [];
        if (mb_strlen($title) < 3) {
            $errors['title'] = __('projects.validation.risk_title');
        }
        if ($likelihood === null) {
            $errors['likelihood'] = __('projects.validation.risk_level');
        }
        if ($impact === null) {
            $errors['impact'] = __('projects.validation.risk_level');
        }

        $ownerId = filled($data['owner_id'] ?? null) ? (int) $data['owner_id'] : null;
        if ($ownerId !== null) {
            $owner = User::query()->find($ownerId);
            if ($owner === null || ! $owner->can('view', $project)) {
                $errors['owner_id'] = __('projects.validation.risk_owner');
            }
        }

        $review = null;
        if (filled($data['review_on'] ?? null)) {
            try {
                $review = CarbonImmutable::parse((string) $data['review_on'])->startOfDay();
            } catch (Throwable) {
                $errors['review_on'] = __('organisation.validation.date');
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $risk->fill([
            'title' => mb_substr($title, 0, 200),
            'description' => filled($data['description'] ?? null) ? mb_substr(trim((string) $data['description']), 0, 3000) : null,
            'likelihood' => $likelihood,
            'impact' => $impact,
            'status' => $status,
            'owner_id' => $ownerId,
            'mitigation' => filled($data['mitigation'] ?? null) ? mb_substr(trim((string) $data['mitigation']), 0, 3000) : null,
            'review_on' => $review,
        ]);
        $risk->project()->associate($project);
        $risk->save();

        return $risk;
    }
}
