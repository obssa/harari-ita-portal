<?php

namespace App\Filament\Resources\ChallengeTeams\Pages;

use App\Domain\Innovation\Models\ChallengeTeam;
use App\Domain\People\Actions\MessagePerson;
use App\Filament\Resources\ChallengeTeams\ChallengeTeamResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
            Action::make('message')
                ->label(__('people.admin.message_team'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn (): bool => CurrentUser::get()->can('message', $team) && MessagePerson::reachable($team->contact_email, $team->contact_phone))
                ->modalDescription(__('people.admin.message_help', ['max' => MessagePerson::MAX_SMS]))
                ->schema([Textarea::make('body')->label(__('people.admin.message_text'))->required()->rows(6)->maxLength(MessagePerson::MAX)])
                ->action(function (array $data) use ($team): void {
                    app(MessagePerson::class)->team($team, (string) $data['body'], CurrentUser::get());
                    Notification::make()->success()->title(__('people.admin.message_sent'))->send();
                }),
        ];
    }
}
