<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Content\Models\Category;
use App\Domain\Media\Actions\SyncMediaUsage;
use App\Domain\Media\Models\Gallery;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Enums\RagStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectMilestone;
use App\Domain\Status\Support\LocalisedText;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Saves a project with its languages (ADR-0027), its pillar, RAG status, spend and milestones (ADR-0024). A Project
 * Manager scoped to a directorate may only save projects of that directorate.
 */
final class SaveProject
{
    public const MAX_MILESTONES = 30;

    public function __construct(
        private readonly WriteTranslations $translations,
        private readonly SyncMediaUsage $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data, User $actor): Project
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $status = ProjectStatus::tryFrom((string) ($data['status'] ?? ProjectStatus::Planned->value)) ?? ProjectStatus::Planned;
        $starts = $this->date($data['starts_on'] ?? null);
        $ends = $this->date($data['ends_on'] ?? null);
        if ($starts !== null && $ends !== null && $ends->lessThan($starts)) {
            throw ValidationException::withMessages(['data.ends_on' => __('projects.validation.dates')]);
        }

        $progress = filled($data['progress'] ?? null) ? (int) $data['progress'] : null;
        if ($progress !== null && ($progress < 0 || $progress > 100)) {
            throw ValidationException::withMessages(['data.progress' => __('projects.validation.progress')]);
        }
        // A completed project is done.
        if ($status === ProjectStatus::Completed) {
            $progress = 100;
        }

        $budget = filled($data['budget'] ?? null) ? round((float) $data['budget'], 2) : null;
        if ($budget !== null && $budget < 0) {
            throw ValidationException::withMessages(['data.budget' => __('projects.validation.budget')]);
        }

        $spent = filled($data['spent'] ?? null) ? round((float) $data['spent'], 2) : null;
        if ($spent !== null && $spent < 0) {
            throw ValidationException::withMessages(['data.spent' => __('projects.validation.budget')]);
        }

        [$latitude, $longitude] = $this->coordinates($data['latitude'] ?? null, $data['longitude'] ?? null);

        $officeId = filled($data['office_id'] ?? null) && Office::query()->whereKey($data['office_id'])->exists() ? (string) $data['office_id'] : null;
        if (! OfficeScope::covers($actor, $officeId, 'projects.manage') || ($project->exists && ! OfficeScope::covers($actor, $project->office_id, 'projects.manage'))) {
            throw ValidationException::withMessages(['data.office_id' => __('projects.validation.office_scope')]);
        }

        $rag = RagStatus::tryFrom((string) ($data['rag_status'] ?? ''));
        $ragReason = filled($data['rag_reason'] ?? null) ? mb_substr(trim((string) $data['rag_reason']), 0, 300) : null;
        $milestones = $this->milestones($data['milestones'] ?? []);

        return DB::transaction(function () use ($project, $data, $translations, $actor, $status, $starts, $ends, $progress, $budget, $spent, $latitude, $longitude, $officeId, $rag, $ragReason, $milestones): Project {
            $ragChanged = ! $project->exists || $project->rag_status !== $rag;
            $project->fill([
                'sector_id' => filled($data['sector_id'] ?? null) && Category::query()->whereKey($data['sector_id'])->where('type', Category::TYPE_SECTOR)->exists() ? (string) $data['sector_id'] : null,
                'office_id' => $officeId,
                'pillar_id' => filled($data['pillar_id'] ?? null) && Pillar::query()->whereKey($data['pillar_id'])->exists() ? (string) $data['pillar_id'] : null,
                'status' => $status,
                'rag_status' => $rag,
                'rag_reason' => $rag !== null ? $ragReason : null,
                'starts_on' => $starts,
                'ends_on' => $ends,
                'budget' => $budget,
                'spent' => $spent,
                'budget_public' => $budget !== null && (bool) ($data['budget_public'] ?? false),
                'progress' => $progress,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'cover_id' => filled($data['cover_id'] ?? null) ? (int) $data['cover_id'] : null,
                'gallery_id' => filled($data['gallery_id'] ?? null) && Gallery::query()->whereKey($data['gallery_id'])->exists() ? (string) $data['gallery_id'] : null,
                'featured' => (bool) ($data['featured'] ?? false),
            ]);
            if (! $project->exists) {
                $project->created_by = $actor->id;
            }
            if ($ragChanged) {
                $project->rag_updated_at = $rag !== null ? CarbonImmutable::now() : null;
            }
            $project->updated_by = $actor->id;
            $project->save();
            $this->syncMilestones($project, $milestones);

            $this->translations->handle($project, $translations, ['summary', 'location'], $actor->can('projects.manage'), 'title');
            $this->usage->handle($project, 'cover', [$project->cover_id]);

            return $project->refresh();
        });
    }

    /**
     * Checks the milestones from the form: a title in at least one language, dates that parse, nothing done in the
     * future, at most MAX_MILESTONES.
     *
     * @return list<array{id: ?int, titles: array<string, string>, due_on: ?CarbonImmutable, done_on: ?CarbonImmutable}>
     */
    private function milestones(mixed $rows): array
    {
        $rows = is_array($rows) ? array_values($rows) : [];
        if (count($rows) > self::MAX_MILESTONES) {
            throw ValidationException::withMessages(['data.milestones' => __('projects.validation.milestones_max', ['max' => self::MAX_MILESTONES])]);
        }

        $clean = [];
        foreach ($rows as $row) {
            $row = is_array($row) ? $row : [];
            $titles = LocalisedText::clean($row['titles'] ?? [], 200);
            if ($titles === []) {
                throw ValidationException::withMessages(['data.milestones' => __('projects.validation.milestone_title')]);
            }
            $done = $this->date($row['done_on'] ?? null);
            if ($done !== null && $done->greaterThan(CarbonImmutable::today())) {
                throw ValidationException::withMessages(['data.milestones' => __('projects.validation.milestone_done')]);
            }
            $clean[] = [
                'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                'titles' => $titles,
                'due_on' => $this->date($row['due_on'] ?? null),
                'done_on' => $done,
            ];
        }

        return $clean;
    }

    /**
     * @param  list<array{id: ?int, titles: array<string, string>, due_on: ?CarbonImmutable, done_on: ?CarbonImmutable}>  $rows
     */
    private function syncMilestones(Project $project, array $rows): void
    {
        $keep = [];
        foreach ($rows as $sort => $row) {
            /** @var ProjectMilestone $milestone */
            $milestone = ($row['id'] !== null ? $project->milestones()->whereKey($row['id'])->first() : null) ?? new ProjectMilestone;
            $milestone->fill(['titles' => $row['titles'], 'due_on' => $row['due_on'], 'done_on' => $row['done_on'], 'sort' => $sort]);
            $milestone->project()->associate($project);
            $milestone->save();
            $keep[] = $milestone->id;
        }
        $project->milestones()->whereNotIn('id', $keep)->get()->each(fn (ProjectMilestone $m) => $m->delete());
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function coordinates(mixed $latitude, mixed $longitude): array
    {
        if (blank($latitude) && blank($longitude)) {
            return [null, null];
        }
        if (! is_numeric($latitude) || ! is_numeric($longitude) || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            throw ValidationException::withMessages(['data.latitude' => __('organisation.validation.coordinates')]);
        }

        return [round((float) $latitude, 6), round((float) $longitude, 6)];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            throw ValidationException::withMessages(['data.starts_on' => __('organisation.validation.date')]);
        }
    }
}
