<?php

namespace App\Filament\Resources\JudgeAssignments\Pages;

use App\Domain\Innovation\Actions\JudgeChallenge;
use App\Domain\Innovation\Models\JudgeAssignment;
use App\Filament\Resources\JudgeAssignments\JudgeAssignmentResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

/**
 * One assignment (ADR-0022): the judge scores each criterion from 1 to 10, or declares a conflict of interest.
 */
class ViewJudgeAssignment extends ViewRecord
{
    protected static string $resource = JudgeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var JudgeAssignment $assignment */
        $assignment = $this->getRecord();
        $open = fn (): bool => CurrentUser::get()->can('score', $assignment)
            && $assignment->conflict_declared_at === null
            && $assignment->team->challenge->results_published_at === null;
        $points = array_combine(range(1, 10), array_map('strval', range(1, 10)));

        return [
            Action::make('score')
                ->label(__('innovation.admin.score'))
                ->icon(Heroicon::OutlinedStar)
                ->visible($open)
                ->fillForm(fn (): array => ['scores' => $assignment->scores ?? [], 'comment' => $assignment->comment])
                ->schema([
                    Grid::make(1)->schema(array_map(
                        fn (array $criterion): Select => Select::make("scores.{$criterion['key']}")
                            ->label(__('innovation.admin.criterion_weight', ['label' => $criterion['label'], 'weight' => $criterion['weight']]))
                            ->options($points)
                            ->required(),
                        $assignment->team->challenge->criteriaIn(app()->getLocale()),
                    )),
                    Textarea::make('comment')->label(__('innovation.field.comment'))->helperText(__('innovation.admin.comment_help'))->rows(3)->maxLength(2000),
                ])
                ->action(function (array $data) use ($assignment): void {
                    app(JudgeChallenge::class)->score($assignment, CurrentUser::get(), (array) ($data['scores'] ?? []), $data['comment'] ?? null);
                    Notification::make()->success()->title(__('innovation.admin.scored'))->send();
                    $this->refreshFormData(['total', 'comment']);
                }),
            Action::make('conflict')
                ->label(__('innovation.admin.declare_conflict'))
                ->icon(Heroicon::OutlinedHandRaised)
                ->color('danger')
                ->visible($open)
                ->modalDescription(__('innovation.admin.declare_conflict_help'))
                ->schema([
                    Textarea::make('note')->label(__('innovation.admin.conflict_note'))->required()->rows(3)->maxLength(500),
                ])
                ->action(function (array $data) use ($assignment): void {
                    app(JudgeChallenge::class)->declareConflict($assignment, CurrentUser::get(), (string) ($data['note'] ?? ''));
                    Notification::make()->success()->title(__('innovation.admin.conflict_declared'))->send();
                    $this->refreshFormData(['total', 'conflict_note']);
                }),
        ];
    }
}
