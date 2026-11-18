<?php

namespace App\Filament\Resources\Programmes;

use App\Domain\Innovation\Enums\ProgrammeKind;
use App\Domain\Innovation\Models\Programme;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Programmes\Pages\CreateProgramme;
use App\Filament\Resources\Programmes\Pages\EditProgramme;
use App\Filament\Resources\Programmes\Pages\ListProgrammes;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\SentenceCaseLabels;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use App\Services\Calendar\EthiopianCalendar;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Programmes in the admin (ADR-0021): the words per language, the questions applicants answer and the cohorts.
 */
class ProgrammeResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Programme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('innovation.admin.programme');
    }

    public static function getPluralModelLabel(): string
    {
        return __('innovation.admin.programmes');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.innovation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'title', 200)->label(__('innovation.field.title')),
                    Translatable::slugField($locale, 'programme_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('innovation.field.summary'))->rows(2)->maxLength(400),
                    Textarea::make("translations.{$locale}.eligibility")->label(__('innovation.field.eligibility'))->rows(3)->maxLength(2000),
                    Textarea::make("translations.{$locale}.benefits")->label(__('innovation.field.benefits'))->helperText(__('admin.services.help.one_per_line'))->rows(4)->maxLength(3000),
                    Textarea::make("translations.{$locale}.timeline")->label(__('innovation.field.timeline'))->helperText(__('innovation.admin.timeline_help'))->rows(4)->maxLength(3000),
                    DirectoryFields::body($locale, __('innovation.field.body')),
                    DirectoryFields::published($locale, 'programmes.manage'),
                ]),
                Section::make(__('innovation.admin.questions'))->description(__('innovation.admin.questions_help'))->schema([
                    Repeater::make('questions')->hiddenLabel()->schema([
                        Hidden::make('key'),
                        Grid::make(2)->schema(array_map(
                            fn (string $locale): TextInput => TextInput::make("labels.{$locale}")->label(Locales::nativeName($locale))->maxLength(300),
                            Locales::all(),
                        )),
                        Grid::make(2)->schema([
                            Toggle::make('long')->label(__('innovation.admin.question_long')),
                            Toggle::make('required')->label(__('innovation.admin.question_required')),
                        ]),
                    ])->maxItems(Programme::MAX_QUESTIONS)->collapsible()->defaultItems(0)->addActionLabel(__('innovation.admin.add_question')),
                ]),
                Section::make(__('innovation.admin.cohorts'))->schema([
                    Repeater::make('cohorts')->hiddenLabel()->schema([
                        Hidden::make('id'),
                        TextInput::make('name')->label(__('innovation.field.cohort_name'))->required()->maxLength(120),
                        Grid::make(2)->schema([
                            DateTimePicker::make('opens_at')->label(__('innovation.field.opens_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())->required(),
                            DateTimePicker::make('closes_at')->label(__('innovation.field.closes_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())->required()->after('opens_at'),
                            DatePicker::make('starts_on')->label(__('innovation.field.starts_on')),
                            DatePicker::make('ends_on')->label(__('innovation.field.ends_on')),
                        ]),
                        TextInput::make('places')->label(__('innovation.field.places'))->numeric()->integer()->minValue(1),
                    ])->collapsible()->defaultItems(0)->addActionLabel(__('innovation.admin.add_cohort')),
                ]),
            ]),
            Section::make(__('admin.posts.section.details'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Select::make('kind')->label(__('innovation.field.kind'))->options(ProgrammeKind::options())->default(ProgrammeKind::Incubation->value)->required(),
                TextInput::make('partner')->label(__('innovation.field.partner'))->helperText(__('innovation.admin.partner_help'))->maxLength(200),
                MediaPicker::make('cover_image_id', [MediaKind::Image])->label(__('admin.offices.field.cover_image')),
                TextInput::make('sort')->label(__('admin.categories.field.sort'))->numeric()->integer()->minValue(0)->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'cohorts'])->withCount('cohorts'))
            ->columns([
                TextColumn::make('title')->label(__('innovation.field.title'))->state(fn (Programme $record): string => $record->title()),
                TextColumn::make('kind')->label(__('innovation.field.kind'))->formatStateUsing(fn (ProgrammeKind $state): string => $state->label())->badge(),
                TextColumn::make('open')->label(__('innovation.field.open_cohort'))
                    ->state(fn (Programme $record): ?string => $record->openCohort()?->name)->placeholder('—'),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgrammes::route('/'),
            'create' => CreateProgramme::route('/create'),
            'edit' => EditProgramme::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Programme>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Programme> */
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
