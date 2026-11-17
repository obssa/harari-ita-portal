<?php

namespace App\Filament\Resources\ChallengeTeams\Pages;

use App\Domain\Innovation\Models\ChallengeTeam;
use App\Filament\Resources\ChallengeTeams\ChallengeTeamResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * One team (ADR-0022): its submission, each judge's total and the award line shown with the results.
 */
class ViewChallengeTeam extends ViewRecord
{
    protected static string $resource = ChallengeTeamResource::class;

    protected function getHeaderActions(): array
    {
        /** @var ChallengeTeam $team */
        $team = $this->getRecord();

        return [
            Action::make('award')
                ->label(__('innovation.field.award'))
                ->icon(Heroicon::OutlinedTrophy)
                ->color('gray')
                ->visible(fn (): bool => CurrentUser::get()->can('award', $team))
                ->fillForm(fn (): array => ['award' => $team->award])
                ->schema([
                    TextInput::make('award')->label(__('innovation.field.award'))->helperText(__('innovation.admin.award_help'))->maxLength(120),
                ])
                ->action(function (array $data) use ($team): void {
                    $award = trim((string) ($data['award'] ?? ''));
                    $team->forceFill(['award' => $award === '' ? null : $award])->save();
                    // The award shows with the published results: touching the challenge purges the cached page.
                    $team->challenge->touch();
                    Notification::make()->success()->title(__('innovation.admin.saved'))->send();
                    $this->refreshFormData(['award']);
                }),
        ];
    }
}
