<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Domain\Innovation\Actions\ReviewApplication;
use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Models\Application;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

/**
 * One application (ADR-0021): the answers, the rubric score and the stage, with the decision note for the applicant.
 */
class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        $application = $this->application();
        $actor = CurrentUser::get();
        $points = array_combine(range(1, 5), array_map('strval', range(1, 5)));

        return [
            Action::make('score')
                ->label(__('innovation.admin.score'))
                ->icon(Heroicon::OutlinedStar)
                ->visible(fn (): bool => $actor->can('review', $application) && $application->stage->isOpen())
                ->fillForm(fn (): array => ['scores' => $application->scores ?? []])
                ->schema([
                    Grid::make(1)->schema(array_map(
                        fn (string $criterion): Select => Select::make("scores.{$criterion}")
                            ->label(__("innovation.criterion.{$criterion}"))
                            ->helperText(__("innovation.criterion_help.{$criterion}"))
                            ->options($points)
                            ->required(),
                        Application::CRITERIA,
                    )),
                ])
                ->action(function (array $data) use ($application): void {
                    app(ReviewApplication::class)->score($application, (array) ($data['scores'] ?? []), CurrentUser::get());
                    Notification::make()->success()->title(__('innovation.admin.scored'))->send();
                    $this->refreshFormData(['score', 'stage']);
                }),
            Action::make('move')
                ->label(__('innovation.admin.move'))
                ->icon(Heroicon::OutlinedArrowRightCircle)
                ->color('gray')
                ->visible(fn (): bool => $actor->can('review', $application) && $application->stage !== ApplicationStage::Withdrawn)
                ->fillForm(fn (): array => ['stage' => $application->stage->value, 'note' => $application->decision_note])
                ->schema([
                    Select::make('stage')->label(__('innovation.field.stage'))->required()
                        ->options(fn (): array => collect(ApplicationStage::options())
                            ->except(array_merge(
                                [ApplicationStage::Withdrawn->value],
                                $actor->can('decide', $application) ? [] : [ApplicationStage::Accepted->value, ApplicationStage::Waitlisted->value, ApplicationStage::Declined->value],
                            ))->all()),
                    Textarea::make('note')->label(__('innovation.admin.decision_note'))->helperText(__('innovation.admin.decision_note_help'))->rows(4)->maxLength(2000),
                ])
                ->action(function (array $data) use ($application): void {
                    app(ReviewApplication::class)->move($application, ApplicationStage::from((string) $data['stage']), $data['note'] ?? null, CurrentUser::get());
                    Notification::make()->success()->title(__('innovation.admin.moved'))->send();
                    $this->refreshFormData(['stage', 'decision_note']);
                }),
        ];
    }

    private function application(): Application
    {
        /** @var Application $application */
        $application = $this->getRecord();

        return $application;
    }
}
