<?php

namespace App\Filament\Resources\Startups\Pages;

use App\Domain\Innovation\Actions\ReviewStartup;
use App\Domain\Innovation\Actions\SaveStartup;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Models\Startup;
use App\Filament\Resources\Startups\StartupResource;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\ViewOnSite;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * A listing (ADR-0020): edit it, review it (list, ask for changes, decline, archive) and verify the label claim.
 */
class EditStartup extends EditRecord
{
    protected static string $resource = StartupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('review')
                ->label(__('innovation.admin.review'))
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->visible(fn (): bool => CurrentUser::get()->can('update', $this->startup()))
                ->fillForm(fn (): array => ['status' => $this->startup()->listing_status->value, 'note' => $this->startup()->review_note])
                ->schema([
                    // ADR-0054: only the decisions this status allows (and the current one, which updates the note).
                    Select::make('status')->label(__('innovation.field.listing'))->required()
                        ->options(fn (): array => collect([$this->startup()->listing_status, ...$this->startup()->listing_status->nextStatuses()])
                            ->mapWithKeys(fn (ListingStatus $status): array => [$status->value => $status->label()])->all()),
                    Textarea::make('note')->label(__('innovation.admin.review_note'))->helperText(__('innovation.admin.review_note_help'))->rows(4)->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    app(ReviewStartup::class)->decide($this->startup(), ListingStatus::from((string) $data['status']), $data['note'] ?? null, CurrentUser::get());
                    Notification::make()->success()->title(__('innovation.admin.reviewed'))->send();
                    $this->refreshFormData(['listing_status']);
                }),
            Action::make('verifyLabel')
                ->label(fn (): string => $this->startup()->label_status === LabelStatus::Verified ? __('innovation.admin.unverify') : __('innovation.admin.verify'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('gray')
                ->visible(fn (): bool => CurrentUser::get()->can('update', $this->startup()) && $this->startup()->label_status !== LabelStatus::None)
                ->requiresConfirmation()
                ->modalDescription(__('innovation.admin.verify_help'))
                ->action(function (): void {
                    app(ReviewStartup::class)->verifyLabel($this->startup(), $this->startup()->label_status !== LabelStatus::Verified, CurrentUser::get());
                    Notification::make()->success()->title(__('innovation.admin.label_saved'))->send();
                    $this->refreshFormData(['label_status']);
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
        $startup = $this->startup();
        $data['translations'] = DirectoryFields::fill($startup, SaveStartup::TEXT_FIELDS);
        $data['founder_name'] = $startup->founder_name;
        $data['founder_email'] = $startup->founder_email;
        $data['founder_phone'] = $startup->founder_phone;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Startup $record */
        return app(SaveStartup::class)->handle($record, $data, CurrentUser::get());
    }

    private function startup(): Startup
    {
        /** @var Startup $startup */
        $startup = $this->getRecord();

        return $startup;
    }
}
