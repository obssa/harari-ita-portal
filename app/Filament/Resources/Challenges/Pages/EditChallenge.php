<?php

namespace App\Filament\Resources\Challenges\Pages;

use App\Domain\Innovation\Actions\JudgeChallenge;
use App\Domain\Innovation\Actions\SaveChallenge;
use App\Domain\Innovation\Models\Challenge;
use App\Filament\Resources\Challenges\ChallengeResource;
use App\Filament\Resources\ChallengeTeams\ChallengeTeamResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * A challenge (ADR-0022), with its teams, the judges' assignment and the publishing of the results.
 */
class EditChallenge extends EditRecord
{
    protected static string $resource = ChallengeResource::class;

    protected function getHeaderActions(): array
    {
        $challenge = $this->challenge();
        $manage = fn (): bool => CurrentUser::get()->can('update', $challenge);

        return [
            Action::make('teams')
                ->label(__('innovation.admin.teams'))
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('gray')
                ->url(fn (): string => ChallengeTeamResource::getUrl('index', ['filters' => ['challenge_id' => ['value' => $challenge->id]]])),
            Action::make('assign')
                ->label(__('innovation.admin.assign_judges'))
                ->icon(Heroicon::OutlinedScale)
                ->color('gray')
                ->visible(fn (): bool => $manage() && $challenge->results_published_at === null)
                ->modalDescription(__('innovation.admin.assign_judges_help'))
                ->schema([
                    Select::make('judges')->label(__('innovation.admin.judges'))->multiple()->required()
                        ->options(fn (): array => User::permission('judging.score')->orderBy('name')->pluck('name', 'id')->all()),
                ])
                ->action(function (array $data) use ($challenge): void {
                    $created = app(JudgeChallenge::class)->assign($challenge, array_values(array_map('intval', (array) ($data['judges'] ?? []))));
                    Notification::make()->success()->title(__('innovation.admin.judges_assigned', ['count' => $created]))->send();
                }),
            Action::make('publish_results')
                ->label(__('innovation.admin.publish_results'))
                ->icon(Heroicon::OutlinedMegaphone)
                ->visible(fn (): bool => $manage() && $challenge->phase() === Challenge::PHASE_JUDGING)
                ->requiresConfirmation()
                ->modalDescription(__('innovation.admin.publish_results_help'))
                ->action(function () use ($challenge): void {
                    $ranked = app(JudgeChallenge::class)->publish($challenge);
                    Notification::make()->success()->title(__('innovation.admin.results_published', ['count' => $ranked]))->send();
                }),
            ...ViewOnSite::header(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->challenge();
        $data['translations'] = DirectoryFields::fill($record, ['title', ...SaveChallenge::TEXT_FIELDS]);
        $data['criteria'] = $record->criteria ?? [];
        $data['registration_opens_at'] = $record->registration_opens_at->toDateTimeString();
        $data['registration_closes_at'] = $record->registration_closes_at->toDateTimeString();
        $data['submission_closes_at'] = $record->submission_closes_at->toDateTimeString();
        $data['results_on'] = $record->results_on?->toDateString();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Challenge $record */
        return app(SaveChallenge::class)->handle($record, $data, CurrentUser::get());
    }

    private function challenge(): Challenge
    {
        /** @var Challenge $challenge */
        $challenge = $this->getRecord();

        return $challenge;
    }
}
