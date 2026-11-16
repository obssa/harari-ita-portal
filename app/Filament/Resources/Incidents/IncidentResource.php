<?php

namespace App\Filament\Resources\Incidents;

use App\Domain\Status\Enums\ComponentState;
use App\Domain\Status\Enums\IncidentKind;
use App\Domain\Status\Enums\IncidentStage;
use App\Domain\Status\Models\Incident;
use App\Domain\Status\Models\StatusComponent;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Incidents\Pages\CreateIncident;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Filament\Support\CalendarDate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Incidents and planned maintenance (ADR-0018): what is wrong, on which systems, and the public updates.
 */
class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 60;

    public static function getModelLabel(): string
    {
        return __('status.admin.incident');
    }

    public static function getPluralModelLabel(): string
    {
        return __('status.admin.incidents');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(__('status.admin.titles'))->columns(2)->schema(array_map(
                    fn (string $locale): TextInput => TextInput::make("titles.{$locale}")
                        ->label(Locales::nativeName($locale))
                        ->maxLength(200)
                        ->extraInputAttributes(['lang' => $locale]),
                    Locales::all(),
                )),
                Section::make(__('status.admin.first_update'))
                    ->description(__('status.admin.first_update_help'))
                    ->visibleOn('create')
                    ->columns(2)
                    ->schema(array_map(
                        fn (string $locale): Textarea => Textarea::make("bodies.{$locale}")
                            ->label(Locales::nativeName($locale))
                            ->rows(3)
                            ->maxLength(2000)
                            ->extraInputAttributes(['lang' => $locale]),
                        Locales::all(),
                    )),
                Section::make(__('status.admin.updates'))
                    ->visibleOn('edit')
                    ->schema([View::make('filament.status.updates')]),
            ]),
            Section::make(__('admin.posts.section.details'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Select::make('kind')->label(__('status.admin.kind'))->options(IncidentKind::options())->default(IncidentKind::Incident->value)->required(),
                Select::make('impact')->label(__('status.admin.impact'))->options(ComponentState::impactOptions())->required(),
                Select::make('components')->label(__('status.admin.components'))->multiple()->required()
                    ->options(fn (): array => StatusComponent::query()->orderBy('sort')->get()
                        ->mapWithKeys(fn (StatusComponent $c): array => [$c->id => $c->name()])->all()),
                DateTimePicker::make('started_at')->label(__('status.admin.started_at'))->seconds(false)->default(now()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('components'))
            ->columns([
                TextColumn::make('title')->label(__('status.admin.title'))->state(fn (Incident $record): string => $record->title())->wrap(),
                TextColumn::make('kind')->label(__('status.admin.kind'))->formatStateUsing(fn (IncidentKind $state): string => $state->label()),
                TextColumn::make('impact')->label(__('status.admin.impact'))->badge()
                    ->formatStateUsing(fn (ComponentState $state): string => $state->label())->color(fn (ComponentState $state): string => $state->color()),
                TextColumn::make('stage')->label(__('status.admin.stage'))->badge()
                    ->formatStateUsing(fn (IncidentStage $state): string => $state->label())
                    ->color(fn (IncidentStage $state): string => $state === IncidentStage::Resolved ? 'success' : 'warning'),
                TextColumn::make('started_at')->label(__('status.admin.started_at'))->formatStateUsing(CalendarDate::format(withTime: true))->sortable(),
                TextColumn::make('resolved_at')->label(__('status.resolved_at'))->formatStateUsing(CalendarDate::format(withTime: true))->placeholder('—'),
            ])
            ->filters([
                Filter::make('open')->label(__('status.admin.open'))->default()->query(fn (Builder $query): Builder => $query->whereNull('resolved_at')),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncidents::route('/'),
            'create' => CreateIncident::route('/create'),
            'edit' => EditIncident::route('/{record}/edit'),
        ];
    }
}
