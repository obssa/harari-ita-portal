<?php

namespace App\Filament\Resources\Volunteers\Pages;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\People\Actions\MessagePerson;
use App\Domain\Volunteers\Actions\ManageVolunteer;
use App\Domain\Volunteers\Enums\VolunteerStatus;
use App\Domain\Volunteers\Models\Volunteer;
use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewVolunteer extends ViewRecord
{
    protected static string $resource = VolunteerResource::class;

    protected function getHeaderActions(): array
    {
        $canManage = fn (): bool => OfficeScope::covers(CurrentUser::get(), $this->volunteer()->office_id, 'volunteers.manage');

        return [
            EditAction::make()->visible($canManage),
            Action::make('status')
                ->label(__('people.admin.change_status'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->color('gray')
                ->visible($canManage)
                ->fillForm(fn (): array => ['status' => $this->volunteer()->status->value])
                ->schema([
                    Select::make('status')->label(__('people.admin.status'))->options(VolunteerStatus::options())->required(),
                    Textarea::make('reason')->label(__('volunteers.admin.status_note'))->rows(3)->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    app(ManageVolunteer::class)->changeStatus($this->volunteer(), VolunteerStatus::from((string) $data['status']), (string) ($data['reason'] ?? ''), CurrentUser::get());
                    Notification::make()->success()->title(__('people.admin.status_changed'))->send();
                    $this->refreshFormData(['status']);
                }),
            Action::make('note')
                ->label(__('people.admin.note'))
                ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->color('gray')
                ->schema([Textarea::make('body')->label(__('people.admin.note_text'))->helperText(__('volunteers.admin.note_help'))->required()->rows(5)->maxLength(2000)])
                ->action(function (array $data): void {
                    app(ManageVolunteer::class)->note($this->volunteer(), (string) $data['body'], CurrentUser::get());
                    Notification::make()->success()->title(__('people.admin.noted'))->send();
                }),
            Action::make('message')
                ->label(__('people.admin.message_volunteer'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn (): bool => $canManage() && $this->volunteer()->erased_at === null && MessagePerson::reachable($this->volunteer()->email, $this->volunteer()->phone))
                ->modalDescription(__('people.admin.message_help', ['max' => MessagePerson::MAX_SMS]))
                ->schema([Textarea::make('body')->label(__('people.admin.message_text'))->required()->rows(6)->maxLength(MessagePerson::MAX)])
                ->action(function (array $data): void {
                    app(MessagePerson::class)->volunteer($this->volunteer(), (string) $data['body'], CurrentUser::get());
                    Notification::make()->success()->title(__('people.admin.message_sent'))->send();
                }),
        ];
    }

    private function volunteer(): Volunteer
    {
        /** @var Volunteer $volunteer */
        $volunteer = $this->getRecord();

        return $volunteer;
    }
}
