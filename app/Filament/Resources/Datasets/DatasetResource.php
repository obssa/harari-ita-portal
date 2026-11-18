<?php

namespace App\Filament\Resources\Datasets;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\OpenData\Enums\Licence;
use App\Domain\OpenData\Enums\UpdateFrequency;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Filament\Resources\Datasets\Pages\CreateDataset;
use App\Filament\Resources\Datasets\Pages\EditDataset;
use App\Filament\Resources\Datasets\Pages\ListDatasets;
use App\Filament\Resources\Datasets\RelationManagers\DistributionsRelationManager;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\SentenceCaseLabels;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Open datasets in the admin (ADR-0025): the description per language, the publisher, licence and update frequency,
 * and the files and links as distributions. The list marks datasets overdue for an update.
 */
class DatasetResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Dataset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('opendata.admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('opendata.admin.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.projects_data');
    }

    /**
     * @return array<string, string>
     */
    public static function officeOptions(): array
    {
        return Office::query()->with('translations')->orderBy('sort')->get()->mapWithKeys(fn (Office $o): array => [$o->id => $o->name()])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function pillarOptions(): array
    {
        return Pillar::query()->with('translations')->orderBy('sort')->get()->mapWithKeys(fn (Pillar $p): array => [$p->id => $p->name()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'title', 200)->label(__('admin.posts.field.title')),
                    Translatable::slugField($locale, 'dataset_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('opendata.field.summary'))->rows(2)->maxLength(500),
                    DirectoryFields::body($locale, __('opendata.field.description')),
                    Textarea::make("translations.{$locale}.methodology")->label(__('opendata.field.methodology'))
                        ->helperText(__('opendata.admin.help.methodology'))->rows(3)->maxLength(3000),
                    TextInput::make("translations.{$locale}.coverage")->label(__('opendata.field.coverage'))
                        ->helperText(__('opendata.admin.help.coverage'))->maxLength(200),
                    DirectoryFields::published($locale, 'datasets.publish'),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('admin.posts.section.details'))->schema([
                    Select::make('office_id')->label(__('opendata.field.publisher'))->searchable()->options(fn (): array => self::officeOptions()),
                    Select::make('pillar_id')->label(__('admin.leaders.field.pillar'))->options(fn (): array => self::pillarOptions()),
                    Select::make('licence')->label(__('opendata.field.licence'))->options(Licence::options())
                        ->default(Licence::CcBy->value)->required()->helperText(__('opendata.admin.help.licence')),
                    Select::make('frequency')->label(__('opendata.field.frequency'))->options(UpdateFrequency::options())
                        ->default(UpdateFrequency::Irregular->value)->required(),
                    DatePicker::make('data_updated_on')->label(__('opendata.field.data_updated_on'))->maxDate(now())
                        ->helperText(__('opendata.admin.help.data_updated_on')),
                    Grid::make(2)->schema([
                        DatePicker::make('temporal_from')->label(__('opendata.field.temporal_from')),
                        DatePicker::make('temporal_to')->label(__('opendata.field.temporal_to'))->afterOrEqual('temporal_from'),
                    ]),
                    TagsInput::make('keywords')->label(__('opendata.field.keywords'))->helperText(__('opendata.admin.help.keywords')),
                    TextInput::make('contact_email')->label(__('opendata.field.contact_email'))->email()->maxLength(190)
                        ->helperText(__('opendata.admin.help.contact_email')),
                    Toggle::make('featured')->label(__('opendata.field.featured')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'office.translations'])->withCount('distributions'))
            ->columns([
                TextColumn::make('title')->label(__('admin.posts.field.title'))->state(fn (Dataset $record): string => $record->title())->wrap(),
                TextColumn::make('publisher')->label(__('opendata.field.publisher'))->state(fn (Dataset $record): ?string => $record->office?->name())->placeholder('—'),
                TextColumn::make('distributions_count')->label(__('opendata.admin.distributions')),
                TextColumn::make('data_updated_on')->label(__('opendata.field.data_updated_on'))->formatStateUsing(CalendarDate::format())->placeholder('—')->sortable(),
                TextColumn::make('next_due')->label(__('opendata.field.next_due'))
                    ->state(fn (Dataset $record): ?string => CalendarDate::format()($record->nextUpdateDue()))
                    ->color(fn (Dataset $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (Dataset $record): ?string => $record->isOverdue() ? __('opendata.overdue') : null)
                    ->placeholder('—'),
                TextColumn::make('download_count')->label(__('opendata.field.downloads'))->sortable(),
                IconColumn::make('featured')->label(__('opendata.field.featured'))->boolean()->toggleable(isToggledHiddenByDefault: true),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([
                SelectFilter::make('office_id')->label(__('opendata.field.publisher'))->options(fn (): array => self::officeOptions()),
                SelectFilter::make('pillar_id')->label(__('admin.leaders.field.pillar'))->options(fn (): array => self::pillarOptions()),
                Filter::make('overdue')->label(__('opendata.overdue'))
                    ->query(fn (Builder $query): Builder => $query->whereIn('id', Dataset::query()->whereNotNull('data_updated_on')->get()
                        ->filter(fn (Dataset $d): bool => $d->isOverdue())->pluck('id'))),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return [DistributionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDatasets::route('/'),
            'create' => CreateDataset::route('/create'),
            'edit' => EditDataset::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Dataset>
     */
    public static function getEloquentQuery(): Builder
    {
        // ADR-0040: a role scoped to directorates reads the datasets those directorates publish.
        /** @var Builder<Dataset> */
        return OfficeScope::apply(parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]), CurrentUser::get(), 'datasets.view');
    }
}
