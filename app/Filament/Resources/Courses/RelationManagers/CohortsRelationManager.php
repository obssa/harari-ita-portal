<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Filament\Resources\CourseCohorts\CourseCohortResource;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A course's cohorts (ADR-0028), soonest last; each opens in the cohorts screen, where its sessions, learners,
 * attendance and certificates are kept.
 */
class CohortsRelationManager extends RelationManager
{
    protected static string $relationship = 'cohorts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('academy.admin.cohorts');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        /** @var Course $course */
        $course = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['enrolments as enrolled_count' => fn (Builder $q) => $q->where('status', 'enrolled')]))
            ->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('academy.field.cohort_name')),
                TextColumn::make('starts_on')->label(__('academy.field.starts_on'))->formatStateUsing(CalendarDate::format()),
                TextColumn::make('ends_on')->label(__('academy.field.ends_on'))->formatStateUsing(CalendarDate::format()),
                TextColumn::make('mode')->label(__('academy.field.mode'))->formatStateUsing(fn (CourseMode $state): string => $state->label()),
                TextColumn::make('enrolled_count')->label(__('academy.admin.enrolled'))
                    ->formatStateUsing(fn (int $state, CourseCohort $record): string => $record->capacity !== null ? "{$state} / {$record->capacity}" : (string) $state),
                TextColumn::make('state')->label(__('academy.admin.state'))->badge()
                    ->state(fn (CourseCohort $record): string => __('academy.state.'.$record->enrolmentState())),
            ])
            ->headerActions([
                Action::make('newCohort')->label(__('academy.admin.new_cohort'))->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => CurrentUser::get()->can('create', CourseCohort::class))
                    ->url(fn (): string => CourseCohortResource::getUrl('create', ['course' => $course->id])),
            ])
            ->recordActions([
                Action::make('open')->label(__('academy.admin.open_cohort'))->icon(Heroicon::OutlinedArrowRight)
                    ->url(fn (CourseCohort $record): string => CourseCohortResource::getUrl(CurrentUser::get()->can('update', $record) ? 'edit' : 'view', ['record' => $record])),
            ]);
    }
}
