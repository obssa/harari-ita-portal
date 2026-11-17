<?php

namespace App\Filament\Resources\Courses;

use App\Domain\Academy\Enums\CourseLevel;
use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\RelationManagers\CohortsRelationManager;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\CheckboxList;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Academy courses in the admin (ADR-0028): what each course teaches per language, its level, mode, length and
 * languages, the partner who runs it, the attendance its certificate needs and the signatory. Its runs are cohorts.
 */
class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return __('academy.admin.course');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy.admin.courses');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.academy');
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
                    Translatable::slugField($locale, 'course_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('academy.field.summary'))->rows(2)->maxLength(500),
                    DirectoryFields::body($locale, __('academy.field.body')),
                    Textarea::make("translations.{$locale}.audience")->label(__('academy.field.audience'))->rows(2)->maxLength(1000),
                    Textarea::make("translations.{$locale}.prerequisites")->label(__('academy.field.prerequisites'))->rows(2)->maxLength(1000),
                    Textarea::make("translations.{$locale}.outcomes")->label(__('academy.field.outcomes'))
                        ->helperText(__('academy.admin.help.lines'))->rows(4)->maxLength(3000),
                    Textarea::make("translations.{$locale}.syllabus")->label(__('academy.field.syllabus'))
                        ->helperText(__('academy.admin.help.lines'))->rows(6)->maxLength(5000),
                    DirectoryFields::published($locale, 'courses.manage'),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('admin.posts.section.details'))->schema([
                    Select::make('level')->label(__('academy.field.level'))->options(CourseLevel::options())->default(CourseLevel::Beginner->value)->required(),
                    Select::make('mode')->label(__('academy.field.mode'))->options(CourseMode::options())->default(CourseMode::InPerson->value)->required()
                        ->helperText(__('academy.admin.help.mode')),
                    TextInput::make('duration_hours')->label(__('academy.field.duration_hours'))->numeric()->minValue(1)->maxValue(2000),
                    CheckboxList::make('languages')->label(__('academy.field.languages'))
                        ->options(collect(Locales::all())->mapWithKeys(fn (string $l): array => [$l => Locales::nativeName($l)])->all())->columns(2),
                    Select::make('pillar_id')->label(__('admin.leaders.field.pillar'))->options(fn (): array => self::pillarOptions()),
                    TextInput::make('partner')->label(__('academy.field.partner'))->helperText(__('academy.admin.help.partner'))->maxLength(200),
                    MediaPicker::make('cover_image_id', [MediaKind::Image])->label(__('admin.offices.field.cover_image')),
                    Toggle::make('featured')->label(__('academy.field.featured')),
                    TextInput::make('sort')->label(__('admin.categories.field.sort'))->numeric()->default(0),
                ]),
                Section::make(__('academy.admin.certificate'))->schema([
                    TextInput::make('attendance_required')->label(__('academy.field.attendance_required'))->numeric()->minValue(50)->maxValue(100)
                        ->default(Course::DEFAULT_ATTENDANCE)->suffix('%')->required()->helperText(__('academy.admin.help.attendance_required')),
                    TextInput::make('signatory_name')->label(__('academy.field.signatory_name'))->maxLength(120)
                        ->helperText(__('academy.admin.help.signatory')),
                    Grid::make(1)->schema(array_map(
                        fn (string $locale): TextInput => TextInput::make("signatory_titles.{$locale}")
                            ->label(__('academy.field.signatory_title').' · '.Locales::nativeName($locale))->maxLength(120),
                        Locales::all(),
                    )),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'cohorts'])->withCount('cohorts'))
            ->columns([
                TextColumn::make('title')->label(__('admin.posts.field.title'))->state(fn (Course $record): string => $record->title())->wrap(),
                TextColumn::make('level')->label(__('academy.field.level'))->formatStateUsing(fn (CourseLevel $state): string => $state->label()),
                TextColumn::make('mode')->label(__('academy.field.mode'))->formatStateUsing(fn (CourseMode $state): string => $state->label()),
                TextColumn::make('next_cohort')->label(__('academy.admin.next_cohort'))
                    ->state(fn (Course $record): ?string => ($next = $record->nextCohort()) instanceof CourseCohort ? CalendarDate::format()($next->starts_on) : null)
                    ->placeholder('—'),
                TextColumn::make('cohorts_count')->label(__('academy.admin.cohorts')),
                TextColumn::make('partner')->label(__('academy.field.partner'))->placeholder('—')->toggleable(),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([
                SelectFilter::make('level')->label(__('academy.field.level'))->options(CourseLevel::options()),
                SelectFilter::make('pillar_id')->label(__('admin.leaders.field.pillar'))->options(fn (): array => self::pillarOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return [CohortsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Course>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Course> */
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
