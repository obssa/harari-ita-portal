<?php

namespace App\Filament\Resources\Challenges;

use App\Domain\Innovation\Models\Challenge;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Challenges\Pages\CreateChallenge;
use App\Filament\Resources\Challenges\Pages\EditChallenge;
use App\Filament\Resources\Challenges\Pages\ListChallenges;
use App\Filament\Support\CalendarDate;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
 * Challenges and hackathons in the admin (ADR-0022): the brief per language, the phase dates and the weighted rubric.
 */
class ChallengeResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Challenge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return __('innovation.admin.challenge');
    }

    public static function getPluralModelLabel(): string
    {
        return __('innovation.admin.challenges');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.innovation');
    }

    public static function form(Schema $schema): Schema
    {
        $picker = fn (string $name, string $label): DateTimePicker => DateTimePicker::make($name)->label($label)->seconds(false)->timezone(EthiopianCalendar::timezone())->required();

        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'title', 200)->label(__('innovation.field.title')),
                    Translatable::slugField($locale, 'challenge_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('innovation.field.summary'))->rows(2)->maxLength(400),
                    Textarea::make("translations.{$locale}.prizes")->label(__('innovation.field.prizes'))->helperText(__('admin.services.help.one_per_line'))->rows(3)->maxLength(2000),
                    Textarea::make("translations.{$locale}.rules")->label(__('innovation.field.rules'))->rows(5)->maxLength(5000),
                    DirectoryFields::body($locale, __('innovation.field.brief')),
                    DirectoryFields::published($locale, 'challenges.manage'),
                ]),
                Section::make(__('innovation.admin.rubric'))->description(__('innovation.admin.rubric_help'))->schema([
                    Repeater::make('criteria')->hiddenLabel()->schema([
                        Hidden::make('key'),
                        Grid::make(2)->schema(array_map(
                            fn (string $locale): TextInput => TextInput::make("labels.{$locale}")->label(Locales::nativeName($locale))->maxLength(120),
                            Locales::all(),
                        )),
                        TextInput::make('weight')->label(__('innovation.field.weight'))->numeric()->integer()->minValue(1)->maxValue(100)->default(1)->required(),
                    ])->maxItems(8)->collapsible()->defaultItems(0)->addActionLabel(__('innovation.admin.add_criterion')),
                ]),
            ]),
            Section::make(__('innovation.admin.phases'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                $picker('registration_opens_at', __('innovation.field.registration_opens_at')),
                $picker('registration_closes_at', __('innovation.field.registration_closes_at'))->after('registration_opens_at'),
                $picker('submission_closes_at', __('innovation.field.submission_closes_at'))->afterOrEqual('registration_closes_at'),
                DatePicker::make('results_on')->label(__('innovation.field.results_on')),
                TextInput::make('max_team_size')->label(__('innovation.field.max_team_size'))->numeric()->integer()->minValue(1)->maxValue(20)->default(5)->required(),
                MediaPicker::make('cover_image_id', [MediaKind::Image])->label(__('admin.offices.field.cover_image')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('registration_opens_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('translations')->withCount(['teams', 'teams as submissions_count' => fn (Builder $q) => $q->whereNotNull('submitted_at')]))
            ->columns([
                TextColumn::make('title')->label(__('innovation.field.title'))->state(fn (Challenge $record): string => $record->title()),
                TextColumn::make('phase')->label(__('innovation.field.phase'))->badge()
                    ->state(fn (Challenge $record): string => __('innovation.phase.'.$record->phase())),
                TextColumn::make('teams_count')->label(__('innovation.admin.teams')),
                TextColumn::make('submissions_count')->label(__('innovation.admin.submissions')),
                TextColumn::make('submission_closes_at')->label(__('innovation.field.submission_closes_at'))->formatStateUsing(CalendarDate::format())->sortable(),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChallenges::route('/'),
            'create' => CreateChallenge::route('/create'),
            'edit' => EditChallenge::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Challenge>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Challenge> */
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
