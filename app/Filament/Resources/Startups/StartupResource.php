<?php

namespace App\Filament\Resources\Startups;

use App\Domain\Content\Models\Category;
use App\Domain\Innovation\Enums\LabelStatus;
use App\Domain\Innovation\Enums\ListingStatus;
use App\Domain\Innovation\Enums\StartupStage;
use App\Domain\Innovation\Models\Startup;
use App\Domain\Media\Enums\MediaKind;
use App\Filament\Resources\Startups\Pages\CreateStartup;
use App\Filament\Resources\Startups\Pages\EditStartup;
use App\Filament\Resources\Startups\Pages\ListStartups;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * The startups directory in the admin (ADR-0020): listings to review, with the founders' contact and the label claim.
 */
class StartupResource extends Resource
{
    protected static ?string $model = Startup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('innovation.admin.startup');
    }

    public static function getPluralModelLabel(): string
    {
        return __('innovation.admin.startups');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.innovation');
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = Startup::query()->where('listing_status', ListingStatus::Submitted->value)->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'name', 200)->label(__('innovation.field.name')),
                    Translatable::slugField($locale, 'startup_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('innovation.field.summary'))->rows(2)->maxLength(400),
                    Textarea::make("translations.{$locale}.products")->label(__('innovation.field.products'))
                        ->helperText(__('admin.services.help.one_per_line'))->rows(3)->maxLength(2000),
                    DirectoryFields::body($locale, __('innovation.field.body')),
                ]),
                Section::make(__('innovation.admin.founders'))->description(__('innovation.admin.founders_help'))->columns(3)->schema([
                    TextInput::make('founder_name')->label(__('support.field.name'))->maxLength(120),
                    TextInput::make('founder_email')->label(__('support.field.email'))->email()->maxLength(190),
                    TextInput::make('founder_phone')->label(__('support.field.phone'))->tel()->maxLength(40),
                ]),
            ]),
            Section::make(__('admin.posts.section.details'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Select::make('stage')->label(__('innovation.field.stage'))->options(StartupStage::options())->required(),
                Select::make('sector_id')->label(__('innovation.field.sector'))
                    ->options(fn (): array => Category::query()->with('translations')->where('type', Category::TYPE_SECTOR)->orderBy('sort')->get()
                        ->mapWithKeys(fn (Category $c): array => [$c->id => $c->name()])->all()),
                Grid::make(2)->schema([
                    TextInput::make('founded_year')->label(__('innovation.field.founded_year'))->numeric()->integer()->minValue(1950)->maxValue((int) now()->format('Y')),
                    TextInput::make('team_size')->label(__('innovation.field.team_size'))->numeric()->integer()->minValue(1),
                ]),
                TextInput::make('area')->label(__('innovation.field.area'))->maxLength(120),
                TextInput::make('website')->label(__('innovation.field.website'))->url()->maxLength(300),
                Select::make('label_status')->label(__('innovation.field.label'))
                    ->options(fn (?Startup $record): array => collect(LabelStatus::options())
                        ->except($record?->label_status === LabelStatus::Verified ? [] : [LabelStatus::Verified->value])->all())
                    ->helperText(__('innovation.admin.label_help'))
                    ->default(LabelStatus::None->value),
                MediaPicker::make('logo_id', [MediaKind::Image])->label(__('innovation.field.logo')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'sector.translations']))
            ->columns([
                TextColumn::make('name')->label(__('innovation.field.name'))->state(fn (Startup $record): string => $record->name())
                    ->description(fn (Startup $record): string => $record->reference),
                TextColumn::make('listing_status')->label(__('innovation.field.listing'))->badge()
                    ->formatStateUsing(fn (ListingStatus $state): string => $state->label())->color(fn (ListingStatus $state): string => $state->color()),
                TextColumn::make('label_status')->label(__('innovation.field.label'))->badge()
                    ->formatStateUsing(fn (LabelStatus $state): string => $state->label())
                    ->color(fn (LabelStatus $state): string => $state === LabelStatus::Verified ? 'success' : 'gray'),
                TextColumn::make('stage')->label(__('innovation.field.stage'))->formatStateUsing(fn (StartupStage $state): string => $state->label()),
                TextColumn::make('sector')->label(__('innovation.field.sector'))->state(fn (Startup $record): ?string => $record->sector?->name())->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label(__('innovation.field.submitted_at'))->formatStateUsing(CalendarDate::format())->sortable(),
            ])
            ->filters([
                SelectFilter::make('listing_status')->label(__('innovation.field.listing'))->options(ListingStatus::options()),
                SelectFilter::make('label_status')->label(__('innovation.field.label'))->options(LabelStatus::options()),
                SelectFilter::make('stage')->label(__('innovation.field.stage'))->options(StartupStage::options()),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewOnSite::row(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStartups::route('/'),
            'create' => CreateStartup::route('/create'),
            'edit' => EditStartup::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Startup>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Startup> */
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
