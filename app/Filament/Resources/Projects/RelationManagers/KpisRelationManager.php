<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Projects\Actions\RecordKpiReading;
use App\Domain\Projects\Actions\SaveKpi;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectKpi;
use App\Domain\Projects\Models\ProjectKpiReading;
use App\Domain\Translation\Support\Locales;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/**
 * A project's KPIs (ADR-0024): the target and baseline, the latest reading against the target, and actions to
 * record or remove a dated reading.
 */
class KpisRelationManager extends RelationManager
{
    protected static string $relationship = 'kpis';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.admin.kpis');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(__('projects.admin.field.kpi_name'))->schema([
                Grid::make(2)->schema(array_map(
                    fn (string $locale): TextInput => TextInput::make("names.{$locale}")->label(Locales::nativeName($locale))->maxLength(160),
                    Locales::all(),
                )),
            ]),
            Section::make(__('projects.admin.field.kpi_unit'))->description(__('projects.admin.help.kpi_unit'))->collapsed()->schema([
                Grid::make(2)->schema(array_map(
                    fn (string $locale): TextInput => TextInput::make("units.{$locale}")->label(Locales::nativeName($locale))->maxLength(40),
                    Locales::all(),
                )),
            ]),
            Grid::make(3)->schema([
                TextInput::make('baseline')->label(__('projects.admin.field.kpi_baseline'))->numeric(),
                TextInput::make('target')->label(__('projects.admin.field.kpi_target'))->numeric()->required(),
                Toggle::make('higher_is_better')->label(__('projects.admin.field.kpi_higher'))->default(true)->inline(false),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();
        $number = fn (?string $value): string => $value === null ? '—' : (string) Number::format((float) $value, maxPrecision: 2, locale: 'en');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('latestReading')->withCount('readings')->orderBy('sort'))
            ->columns([
                TextColumn::make('name')->label(__('projects.admin.field.kpi_name'))
                    ->state(fn (ProjectKpi $record): string => $record->name())->wrap(),
                TextColumn::make('baseline')->label(__('projects.admin.field.kpi_baseline'))
                    ->formatStateUsing(fn (?string $state): string => $number($state))->placeholder('—'),
                TextColumn::make('target')->label(__('projects.admin.field.kpi_target'))
                    ->formatStateUsing(fn (ProjectKpi $record): string => trim($number($record->target).' '.$record->unit())),
                TextColumn::make('latest')->label(__('projects.admin.field.kpi_latest'))
                    ->state(fn (ProjectKpi $record): ?string => $record->latestReading === null ? null
                        : $number($record->latestReading->value).' · '.CalendarDate::format()($record->latestReading->as_of))
                    ->placeholder('—'),
                TextColumn::make('progress')->label(__('projects.admin.field.progress'))
                    ->state(fn (ProjectKpi $record): ?int => $record->progress())->suffix('%')->placeholder('—'),
                TextColumn::make('readings_count')->label(__('projects.admin.field.kpi_readings')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('projects.admin.add_kpi'))
                    ->using(fn (array $data): ProjectKpi => app(SaveKpi::class)->handle($project, new ProjectKpi, $data, CurrentUser::get())),
            ])
            ->recordActions([
                Action::make('reading')->label(__('projects.admin.record_reading'))->icon(Heroicon::OutlinedPlusCircle)
                    ->visible(fn (ProjectKpi $record): bool => CurrentUser::get()->can('update', $record))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('value')->label(__('projects.admin.field.kpi_value'))->numeric()->required(),
                            DatePicker::make('as_of')->label(__('projects.admin.field.as_of'))->required()
                                ->default(CarbonImmutable::today()->toDateString())->maxDate(CarbonImmutable::today()),
                        ]),
                        TextInput::make('note')->label(__('projects.admin.field.kpi_note'))->maxLength(300),
                    ])
                    ->action(function (ProjectKpi $record, array $data): void {
                        app(RecordKpiReading::class)->handle($record, $data, CurrentUser::get());
                        Notification::make()->success()->title(__('projects.admin.reading_saved'))->send();
                    }),
                Action::make('removeReading')->label(__('projects.admin.remove_reading'))->icon(Heroicon::OutlinedMinusCircle)->color('danger')
                    ->visible(fn (ProjectKpi $record): bool => $record->readings_count > 0 && CurrentUser::get()->can('update', $record))
                    ->schema(fn (ProjectKpi $record): array => [
                        Select::make('reading')->label(__('projects.admin.field.as_of'))->required()
                            ->options($record->readings()->orderByDesc('as_of')->get()
                                ->mapWithKeys(fn (ProjectKpiReading $r): array => [$r->id => CalendarDate::format()($r->as_of).' · '.$number($r->value)])->all()),
                    ])
                    ->requiresConfirmation()
                    ->action(function (ProjectKpi $record, array $data): void {
                        $reading = $record->readings()->whereKey((int) $data['reading'])->firstOrFail();
                        app(RecordKpiReading::class)->remove($reading, CurrentUser::get());
                    }),
                EditAction::make()
                    ->using(fn (ProjectKpi $record, array $data): ProjectKpi => app(SaveKpi::class)->handle($project, $record, $data, CurrentUser::get())),
                DeleteAction::make()->after(fn () => $project->touch()),
            ]);
    }
}
