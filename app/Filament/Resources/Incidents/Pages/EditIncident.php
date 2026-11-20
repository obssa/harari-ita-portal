<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Domain\Status\Actions\PostIncidentUpdate;
use App\Domain\Status\Actions\SaveIncident;
use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Models\Incident;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Support\CurrentUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * An incident (ADR-0018): its details, the public updates so far, and "Post an update" to move it on or resolve it.
 */
class EditIncident extends EditRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('postUpdate')
                ->label(__('status.admin.post_update'))
                ->icon(Heroicon::OutlinedMegaphone)
                ->visible(fn (): bool => CurrentUser::get()->can('update', $this->incident()))
                ->fillForm(fn (): array => ['stage' => $this->incident()->stage->value])
                ->schema([
                    // ADR-0054: the current stage (a further update) and the stages it can move to.
                    Select::make('stage')->label(__('status.admin.stage'))->required()
                        ->options(fn (): array => collect([$this->incident()->stage, ...$this->incident()->stage->nextStages()])
                            ->mapWithKeys(fn (IncidentStage $stage): array => [$stage->value => $stage->label()])->all()),
                    ...array_map(
                        fn (string $locale): Textarea => Textarea::make("bodies.{$locale}")
                            ->label(Locales::nativeName($locale))
                            ->rows(3)
                            ->maxLength(2000)
                            ->requiredWithoutAll(collect(Locales::all())->reject(fn (string $other): bool => $other === $locale)->map(fn (string $other): string => "bodies.{$other}")->implode(','))
                            ->extraInputAttributes(['lang' => $locale]),
                        Locales::all(),
                    ),
                ])
                ->action(function (array $data): void {
                    app(PostIncidentUpdate::class)->handle($this->incident(), IncidentStage::from((string) $data['stage']), (array) ($data['bodies'] ?? []), CurrentUser::get());
                    Notification::make()->success()->title(__('status.admin.posted'))->send();
                    $this->refreshFormData(['stage', 'resolved_at']);
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['components'] = $this->incident()->components()->pluck('status_components.id')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Incident $record */
        return app(SaveIncident::class)->handle($record, $data, CurrentUser::get());
    }

    private function incident(): Incident
    {
        /** @var Incident $incident */
        $incident = $this->getRecord();

        return $incident;
    }
}
