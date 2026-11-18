<?php

namespace App\Filament\Resources\CourseCohorts;

use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Academy\Support\CohortScope;
use App\Domain\Organisation\Models\Office;
use App\Domain\Status\Support\LocalisedText;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\CourseCohorts\Pages\CreateCourseCohort;
use App\Filament\Resources\CourseCohorts\Pages\EditCourseCohort;
use App\Filament\Resources\CourseCohorts\Pages\ListCourseCohorts;
use App\Filament\Resources\CourseCohorts\Pages\ViewCourseCohort;
use App\Filament\Resources\CourseCohorts\RelationManagers\EnrolmentsRelationManager;
use App\Filament\Resources\CourseCohorts\RelationManagers\SessionsRelationManager;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\SentenceCaseLabels;
use App\Services\Calendar\EthiopianCalendar;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Cohorts in the admin (ADR-0028): one run of a course with its dates, venue, enrolment window, places, instructors
 * and sessions; its learners, attendance and certificates below. Instructors see only the cohorts they teach.
 */
class CourseCohortResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = CourseCohort::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('academy.admin.cohort');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy.admin.cohorts');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.academy');
    }

    /**
     * @return array<string, string>
     */
    public static function courseOptions(): array
    {
        return Course::query()->with('translations')->orderBy('sort')->get()->mapWithKeys(fn (Course $c): array => [$c->id => $c->title()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(__('academy.admin.cohort'))->schema([
                    Select::make('course_id')->label(__('academy.admin.course'))->options(fn (): array => self::courseOptions())
                        ->searchable()->required()->disabledOn('edit')->dehydrated(fn (string $operation): bool => $operation === 'create'),
                    Grid::make(2)->schema([
                        TextInput::make('name')->label(__('academy.field.cohort_name'))->helperText(__('academy.admin.help.cohort_name'))->required()->maxLength(120),
                        Select::make('mode')->label(__('academy.field.mode'))->options(CourseMode::options())->helperText(__('academy.admin.help.cohort_mode')),
                    ]),
                    Grid::make(2)->schema([
                        DatePicker::make('starts_on')->label(__('academy.field.starts_on'))->required(),
                        DatePicker::make('ends_on')->label(__('academy.field.ends_on'))->required()->afterOrEqual('starts_on'),
                    ]),
                    Select::make('office_id')->label(__('academy.field.venue'))->searchable()
                        ->options(fn (): array => Office::query()->with('translations')->orderBy('sort')->get()->mapWithKeys(fn (Office $o): array => [$o->id => $o->name()])->all())
                        ->helperText(__('academy.admin.help.venue')),
                    Section::make(__('academy.field.venue_note'))->description(__('academy.admin.help.venue_note'))->collapsed()->schema([
                        Grid::make(2)->schema(array_map(
                            fn (string $locale): TextInput => TextInput::make("venue_notes.{$locale}")->label(Locales::nativeName($locale))->maxLength(300),
                            Locales::all(),
                        )),
                    ]),
                ]),
                Section::make(__('academy.admin.sessions'))->description(__('academy.admin.help.sessions'))->schema([
                    Repeater::make('sessions')->hiddenLabel()->schema([
                        Hidden::make('id'),
                        Grid::make(2)->schema([
                            DateTimePicker::make('starts_at')->label(__('academy.field.session_starts'))->seconds(false)->timezone(EthiopianCalendar::timezone())->required(),
                            DateTimePicker::make('ends_at')->label(__('academy.field.session_ends'))->seconds(false)->timezone(EthiopianCalendar::timezone())->required()->after('starts_at'),
                        ]),
                        Grid::make(2)->schema(array_map(
                            fn (string $locale): TextInput => TextInput::make("titles.{$locale}")->label(__('academy.field.session_title').' · '.Locales::nativeName($locale))->maxLength(160),
                            Locales::all(),
                        )),
                    ])->maxItems(60)->collapsible()->collapsed()->defaultItems(0)->addActionLabel(__('academy.admin.add_session'))
                        ->itemLabel(fn (array $state): ?string => filled($state['starts_at'] ?? null)
                            ? trim(CalendarDate::format(withTime: true)((string) $state['starts_at']).' · '.LocalisedText::pick(is_array($state['titles'] ?? null) ? $state['titles'] : []), ' ·')
                            : null),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('academy.admin.enrolment'))->schema([
                    DateTimePicker::make('enrol_opens_at')->label(__('academy.field.enrol_opens_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())->helperText(__('academy.admin.help.enrol_opens_at')),
                    DateTimePicker::make('enrol_closes_at')->label(__('academy.field.enrol_closes_at'))->seconds(false)->timezone(EthiopianCalendar::timezone())->after('enrol_opens_at')
                        ->helperText(__('academy.admin.help.enrol_closes_at')),
                    TextInput::make('capacity')->label(__('academy.field.capacity'))->numeric()->minValue(1)->maxValue(10000)->helperText(__('academy.admin.help.capacity')),
                    Toggle::make('waitlist')->label(__('academy.field.waitlist'))->default(true),
                ]),
                Section::make(__('academy.admin.instructors'))->schema([
                    Select::make('instructors')->hiddenLabel()->multiple()->searchable()
                        ->options(fn (): array => Instructor::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText(__('academy.admin.help.instructors')),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('course.translations')
                ->withCount(['enrolments as enrolled_count' => fn (Builder $q) => $q->where('status', 'enrolled')]))
            ->columns([
                TextColumn::make('course')->label(__('academy.admin.course'))->state(fn (CourseCohort $record): string => $record->course->title())->wrap(),
                TextColumn::make('name')->label(__('academy.field.cohort_name'))->searchable(),
                TextColumn::make('starts_on')->label(__('academy.field.starts_on'))->formatStateUsing(CalendarDate::format())->sortable(),
                TextColumn::make('ends_on')->label(__('academy.field.ends_on'))->formatStateUsing(CalendarDate::format())->toggleable(),
                TextColumn::make('enrolled_count')->label(__('academy.admin.enrolled'))
                    ->formatStateUsing(fn (int $state, CourseCohort $record): string => $record->capacity !== null ? "{$state} / {$record->capacity}" : (string) $state),
                TextColumn::make('state')->label(__('academy.admin.state'))->badge()
                    ->state(fn (CourseCohort $record): string => __('academy.state.'.$record->enrolmentState()))
                    ->color(fn (CourseCohort $record): string => match ($record->enrolmentState()) {
                        CourseCohort::STATE_OPEN => 'success',
                        CourseCohort::STATE_WAITLIST, CourseCohort::STATE_SOON => 'warning',
                        CourseCohort::STATE_CANCELLED, CourseCohort::STATE_FULL => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('course_id')->label(__('academy.admin.course'))->options(fn (): array => self::courseOptions()),
                Filter::make('upcoming')->label(__('academy.admin.upcoming'))->default()
                    ->query(fn (Builder $query): Builder => $query->whereDate('ends_on', '>=', now()->toDateString())),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn (CourseCohort $record): bool => ! CurrentUser::get()->can('update', $record)),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [SessionsRelationManager::class, EnrolmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseCohorts::route('/'),
            'create' => CreateCourseCohort::route('/create'),
            'view' => ViewCourseCohort::route('/{record}'),
            'edit' => EditCourseCohort::route('/{record}/edit'),
        ];
    }

    /**
     * Instructors see only the cohorts they teach.
     *
     * @return Builder<CourseCohort>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<CourseCohort> */
        return CohortScope::apply(parent::getEloquentQuery(), CurrentUser::get());
    }
}
