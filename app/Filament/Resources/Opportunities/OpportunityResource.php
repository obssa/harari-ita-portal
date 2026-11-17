<?php

namespace App\Filament\Resources\Opportunities;

use App\Domain\Documents\Models\Document;
use App\Domain\Opportunities\Enums\EmploymentType;
use App\Domain\Opportunities\Enums\OpportunityType;
use App\Domain\Opportunities\Models\Opportunity;
use App\Domain\Organisation\Models\Office;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Filament\Resources\Opportunities\RelationManagers\AddendaRelationManager;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use App\Services\Calendar\EthiopianCalendar;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Opportunities in the admin (ADR-0032): tenders, calls for proposals, vacancies and internships, with their dates,
 * where to apply, the library documents they attach and, below, the log of addenda (ADR-0033).
 */
class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 60;

    public static function getModelLabel(): string
    {
        return __('opportunities.admin.opportunity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('opportunities.admin.opportunities');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.opportunities');
    }

    /**
     * Library documents to attach, newest first.
     *
     * @return array<string, string>
     */
    public static function documentOptions(): array
    {
        return Document::query()->with('translations')->latest()->limit(500)->get()
            ->mapWithKeys(fn (Document $d): array => [(string) $d->getKey() => $d->title().' · '.$d->type->label()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $isJob = fn (Get $get): bool => OpportunityType::tryFrom((string) $get('type'))?->isJob() ?? false;

        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'title', 200)->label(__('admin.posts.field.title')),
                    Translatable::slugField($locale, 'opportunity_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('opportunities.field.summary'))->rows(2)->maxLength(500),
                    DirectoryFields::body($locale, __('opportunities.field.body')),
                    Textarea::make("translations.{$locale}.eligibility")->label(__('opportunities.field.eligibility'))
                        ->helperText(__('opportunities.admin.help.lines'))->rows(4)->maxLength(3000),
                    Textarea::make("translations.{$locale}.how_to_apply")->label(__('opportunities.field.how_to_apply'))->rows(3)->maxLength(3000),
                    Textarea::make("translations.{$locale}.outcome")->label(__('opportunities.field.outcome'))
                        ->helperText(__('opportunities.admin.help.outcome'))->rows(3)->maxLength(3000),
                    DirectoryFields::published($locale, 'opportunities.manage'),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('admin.posts.section.details'))->schema([
                    Select::make('type')->label(__('opportunities.field.type'))->options(OpportunityType::options())
                        ->default(OpportunityType::Tender->value)->required()->live(),
                    TextInput::make('reference')->label(__('opportunities.field.reference'))->helperText(__('opportunities.admin.help.reference'))->maxLength(60),
                    Select::make('office_id')->label(__('opportunities.field.office'))->searchable()
                        ->options(fn (): array => Office::query()->with('translations')->orderBy('sort')->get()->mapWithKeys(fn (Office $o): array => [$o->id => $o->name()])->all()),
                    Select::make('pillar_id')->label(__('admin.leaders.field.pillar'))->options(fn (): array => CourseResource::pillarOptions()),
                    Toggle::make('featured')->label(__('opportunities.field.featured')),
                ]),
                Section::make(__('opportunities.admin.dates'))->description(__('opportunities.admin.help.dates'))->schema([
                    DateTimePicker::make('opens_at')->label(__('opportunities.field.opens_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())
                        ->helperText(__('opportunities.admin.help.opens_at')),
                    DateTimePicker::make('closes_at')->label(__('opportunities.field.closes_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())->required()
                        ->disabled(fn (?Opportunity $record): bool => $record !== null && ($record->isCancelled() || $record->addenda()->exists()))
                        ->helperText(fn (?Opportunity $record): string => $record !== null && $record->addenda()->exists() ? __('opportunities.admin.help.closes_locked') : __('opportunities.admin.help.closes_at')),
                    DateTimePicker::make('bid_opening_at')->label(__('opportunities.field.bid_opening_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())
                        ->visible(fn (Get $get): bool => $get('type') === OpportunityType::Tender->value),
                ]),
                Section::make(__('opportunities.admin.apply'))->schema([
                    TextInput::make('positions')->label(__('opportunities.field.positions'))->numeric()->minValue(1)->maxValue(1000)->visible($isJob),
                    Select::make('employment_type')->label(__('opportunities.field.employment_type'))->options(EmploymentType::options())
                        ->visible(fn (Get $get): bool => $get('type') === OpportunityType::Vacancy->value),
                    TextInput::make('apply_url')->label(__('opportunities.field.apply_url'))->url()->maxLength(500)->helperText(__('opportunities.admin.help.apply_url')),
                    TextInput::make('apply_email')->label(__('opportunities.field.apply_email'))->email()->maxLength(190)->helperText(__('opportunities.admin.help.apply_email')),
                ]),
                Section::make(__('opportunities.field.documents'))->description(__('opportunities.admin.help.documents'))->schema([
                    Select::make('documents')->hiddenLabel()->multiple()->searchable()->options(fn (): array => self::documentOptions()),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('closes_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('translations')->withCount('addenda'))
            ->columns([
                TextColumn::make('title')->label(__('admin.posts.field.title'))->state(fn (Opportunity $record): string => $record->title())->wrap(),
                TextColumn::make('type')->label(__('opportunities.field.type'))->badge()->formatStateUsing(fn (OpportunityType $state): string => $state->label()),
                TextColumn::make('reference')->label(__('opportunities.field.reference'))->placeholder('—')->searchable()->fontFamily('mono'),
                TextColumn::make('closes_at')->label(__('opportunities.field.closes_at'))->formatStateUsing(CalendarDate::format(withTime: true))->sortable(),
                TextColumn::make('state')->label(__('opportunities.admin.state'))->badge()
                    ->state(fn (Opportunity $record): string => __('opportunities.state.'.$record->state()))
                    ->color(fn (Opportunity $record): string => match ($record->state()) {
                        Opportunity::STATE_OPEN => 'success',
                        Opportunity::STATE_CLOSING => 'warning',
                        Opportunity::STATE_UPCOMING => 'info',
                        Opportunity::STATE_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('addenda_count')->label(__('opportunities.admin.addenda')),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('opportunities.field.type'))->options(OpportunityType::options()),
                Filter::make('current')->label(__('opportunities.admin.current'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('cancelled_at')->where('closes_at', '>', now())),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return [AddendaRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Opportunity>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Opportunity> */
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
