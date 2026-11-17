<?php

namespace App\Domain\Dashboard\Support;

use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Support\CohortScope;
use App\Domain\Access\Enums\ScopeType;
use App\Domain\Access\Enums\SystemRole;
use App\Domain\Access\Support\OfficeScope;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Models\PostTranslation;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Models\Application;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Opportunities\Models\OpportunityAddendum;
use App\Domain\Organisation\Models\Office;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Enums\RagStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Support\Enums\SlaState;
use App\Domain\Support\Enums\TicketStatus;
use App\Domain\Support\Models\Ticket;
use App\Models\User;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The weekly digest (PLAN.md §6, ADR-0039): the past week and the week ahead of the directorates a person is
 * responsible for, each section only for what they may see in the admin. Counts and titles, never personal data.
 * A section with nothing to report is left out; a digest with no section is not sent.
 */
final class WeeklyDigest
{
    /** Directorate heads, Admins and Super Admins receive it. */
    public static function concerns(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasRole([SystemRole::Admin->value, SystemRole::DirectorateHead->value]);
    }

    /**
     * The names of the directorates the digest covers, or an empty list for the whole Agency.
     *
     * @return list<string>
     */
    public static function directorates(User $user, string $locale): array
    {
        if ($user->isSuperAdmin() || $user->hasRole(SystemRole::Admin->value)) {
            return [];
        }
        $ids = $user->roleScopes()->where('scope_type', ScopeType::Office->value)->pluck('scope_value')->unique()->values()->all();

        return Office::query()->with('translations')->whereKey($ids)->orderBy('sort')->get()->map(fn (Office $o): string => $o->name($locale))->all();
    }

    /**
     * @return list<array{title: string, lines: list<string>}>
     */
    public static function sections(User $user, string $locale, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $since = $now->subWeek();
        $ahead = $now->addWeek();
        $date = fn (CarbonImmutable $at): string => app(EthiopianCalendar::class)->format($at, $locale);
        $t = fn (string $key, array $replace = []): string => __("dashboard.digest.{$key}", $replace, $locale);

        $sections = [];

        if ($user->can('tickets.view')) {
            $tickets = fn (): Builder => OfficeScope::apply(Ticket::query(), $user, 'tickets.view');
            $open = $tickets()->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value, TicketStatus::Waiting->value])
                ->select(['id', 'status', 'resolve_due_at', 'resolved_at', 'closed_at'])->limit(5000)->get();
            $opened = $tickets()->where('created_at', '>=', $since)->count();
            $resolved = $tickets()->where('resolved_at', '>=', $since)->count();
            $breached = $open->filter(fn (Ticket $ticket): bool => $ticket->slaState() === SlaState::Breached)->count();
            if ($opened + $resolved + $open->count() > 0) {
                $sections[] = ['title' => $t('tickets'), 'lines' => array_values(array_filter([
                    $t('tickets_opened', ['count' => $opened]),
                    $t('tickets_resolved', ['count' => $resolved]),
                    $t('tickets_open', ['count' => $open->count()]),
                    $breached > 0 ? $t('tickets_breached', ['count' => $breached]) : null,
                ]))];
            }
        }

        if ($user->can('projects.view')) {
            $running = OfficeScope::apply(Project::query(), $user, 'projects.view')->with('translations')->where('status', ProjectStatus::Ongoing->value)->get();
            if ($running->isNotEmpty()) {
                $lines = [$t('projects_running', [
                    'count' => $running->count(),
                    'green' => $running->where('rag_status', RagStatus::Green)->count(),
                    'amber' => $running->where('rag_status', RagStatus::Amber)->count(),
                    'red' => $running->where('rag_status', RagStatus::Red)->count(),
                ])];
                foreach ($running->whereIn('rag_status', [RagStatus::Red, RagStatus::Amber])->take(5) as $project) {
                    $lines[] = $project->rag_status?->label().': '.$project->title($locale);
                }
                $sections[] = ['title' => $t('projects'), 'lines' => $lines];
            }
        }

        if ($user->can('datasets.view')) {
            $overdue = OfficeScope::apply(Dataset::query(), $user, 'datasets.view')->with('translations')->whereNotNull('data_updated_on')->limit(500)->get()
                ->filter(fn (Dataset $dataset): bool => $dataset->isOverdue($now->startOfDay()));
            if ($overdue->isNotEmpty()) {
                $sections[] = ['title' => $t('datasets'), 'lines' => [
                    $t('datasets_overdue', ['count' => $overdue->count()]),
                    ...$overdue->take(5)->map(fn (Dataset $d): string => $d->title($locale))->values()->all(),
                ]];
            }
        }

        if ($user->can('opportunities.view')) {
            $scoped = fn (): Builder => OfficeScope::apply(Opportunity::query(), $user, 'opportunities.view');
            $closing = $scoped()->with('translations')->whereNull('cancelled_at')->whereBetween('closes_at', [$now, $ahead])->orderBy('closes_at')->get();
            $entries = OpportunityAddendum::query()->whereIn('opportunity_id', $scoped()->select('id'))->where('published_at', '>=', $since)->count();
            if ($closing->isNotEmpty() || $entries > 0) {
                $sections[] = ['title' => $t('opportunities'), 'lines' => [
                    ...$closing->take(6)->map(fn (Opportunity $o): string => $t('opportunity_closes', ['title' => $o->title($locale), 'date' => $date($o->closes_at)]))->values()->all(),
                    ...($entries > 0 ? [$t('opportunity_entries', ['count' => $entries])] : []),
                ]];
            }
        }

        if ($user->can('cohorts.view')) {
            /** @var Builder<CourseCohort> $cohorts */
            $cohorts = CohortScope::apply(CourseCohort::query(), $user);
            $starting = $cohorts->with('course.translations')->whereNull('cancelled_at')
                ->whereBetween('starts_on', [$now->toDateString(), $ahead->toDateString()])
                ->withCount(['enrolments as enrolled_total' => fn (Builder $q) => $q->where('status', EnrolmentStatus::Enrolled->value)])
                ->orderBy('starts_on')->get();
            if ($starting->isNotEmpty()) {
                $sections[] = ['title' => $t('academy'), 'lines' => $starting->take(6)->map(fn (CourseCohort $c): string => $t('cohort_starts', [
                    'title' => $c->course->title($locale).' · '.$c->name, 'date' => $date($c->starts_on), 'count' => (int) $c->getAttribute('enrolled_total'),
                ]))->values()->all()];
            }
        }

        if ($user->can('applications.view')) {
            $received = Application::query()->where('created_at', '>=', $since)->count();
            $waiting = Application::query()->whereIn('stage', [ApplicationStage::Received->value, ApplicationStage::Screening->value, ApplicationStage::Interview->value])->count();
            if ($received + $waiting > 0) {
                $sections[] = ['title' => $t('innovation'), 'lines' => [
                    $t('applications_received', ['count' => $received]),
                    $t('applications_waiting', ['count' => $waiting]),
                ]];
            }
        }

        if ($user->can('posts.view')) {
            $offices = OfficeScope::officeIds($user, 'posts.view');
            $posts = fn (): Builder => PostTranslation::query()
                ->when($offices !== null, fn (Builder $q) => $q->whereHas('post.offices', fn (Builder $o) => $o->whereIn('offices.id', $offices)));
            $published = $posts()->where('status', ContentStatus::Published->value)->whereBetween('published_at', [$since, $now])->count();
            $review = $posts()->where('status', ContentStatus::InReview->value)->count();
            if ($published + $review > 0) {
                $sections[] = ['title' => $t('content'), 'lines' => [
                    $t('content_published', ['count' => $published]),
                    $t('content_review', ['count' => $review]),
                ]];
            }
        }

        return $sections;
    }
}
