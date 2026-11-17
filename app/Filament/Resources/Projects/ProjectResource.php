<?php

namespace App\Filament\Resources\Projects;

use App\Domain\Access\Support\OfficeScope;
use App\Domain\Content\Models\Category;
use App\Domain\Media\Enums\MediaKind;
use App\Domain\Media\Models\Gallery;
use App\Domain\Organisation\Models\Office;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Projects\Enums\RagStatus;
use App\Domain\Projects\Models\Project;
use App\Domain\Status\Support\LocalisedText;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\RelationManagers\KpisRelationManager;
use App\Filament\Resources\Projects\RelationManagers\RisksRelationManager;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\MediaPicker;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 23;

    public static function getModelLabel(): string
    {
        return __('projects.admin.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.admin.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.projects_data');
    }

    /**
     * @return array<int|string, string>
     */
    public static function sectorOptions(): array
    {
        return Category::query()->where('type', Category::TYPE_SECTOR)->with('translations')->orderBy('sort')->get()
            ->mapWithKeys(fn (Category $c): array => [$c->id => $c->name()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'title', 200)->label(__('admin.posts.field.title')),
                    Translatable::slugField($locale, 'project_translations'),
                    Textarea::make("translations.{$locale}.summary")->label(__('projects.admin.field.summary'))->rows(2)->maxLength(500),
                    TextInput::make("translations.{$locale}.location")->label(__('projects.admin.field.location'))
                        ->helperText(__('projects.admin.help.location'))->maxLength(200),
                    DirectoryFields::body($locale, __('projects.admin.field.body')),
                    DirectoryFields::published($locale, 'projects.manage'),
                ]),
                Section::make(__('projects.admin.milestones'))->description(__('projects.admin.help.milestones'))->schema([
                    Repeater::make('milestones')->hiddenLabel()->schema([
                        Hidden::make('id'),
                        Grid::make(2)->schema(array_map(
                            fn (string $locale): TextInput => TextInput::make("titles.{$locale}")->label(Locales::nativeName($locale))->maxLength(200),
                            Locales::all(),
                        )),
                        Grid::make(2)->schema([
                            DatePicker::make('due_on')->label(__('projects.admin.field.due_on')),
                            DatePicker::make('done_on')->label(__('projects.admin.field.done_on'))->maxDate(now()),
                        ]),
                    ])->maxItems(30)->collapsible()->reorderable()->defaultItems(0)->addActionLabel(__('projects.admin.add_milestone'))
                        ->itemLabel(fn (array $state): ?string => LocalisedText::pick(is_array($state['titles'] ?? null) ? $state['titles'] : []) ?: null),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('admin.posts.section.details'))->schema([
                    Select::make('status')->label(__('projects.admin.field.status'))->options(ProjectStatus::options())
                        ->default(ProjectStatus::Planned->value)->required(),
                    TextInput::make('progress')->label(__('projects.admin.field.progress'))->numeric()->integer()
                        ->minValue(0)->maxValue(100)->suffix('%'),
                    Select::make('rag_status')->label(__('projects.admin.field.rag'))->options(RagStatus::options())
                        ->helperText(__('projects.admin.help.rag'))->live(),
                    Textarea::make('rag_reason')->label(__('projects.admin.field.rag_reason'))->rows(2)->maxLength(300)
                        ->helperText(__('projects.admin.help.rag_reason'))->visible(fn (Get $get): bool => filled($get('rag_status'))),
                    Select::make('pillar_id')->label(__('admin.leaders.field.pillar'))
                        ->options(fn (): array => Pillar::query()->with('translations')->orderBy('sort')->get()
                            ->mapWithKeys(fn (Pillar $p): array => [$p->id => $p->name()])->all()),
                    Select::make('sector_id')->label(__('projects.admin.field.sector'))
                        ->helperText(__('projects.admin.help.sector'))
                        ->options(fn (): array => self::sectorOptions()),
                    Select::make('office_id')->label(__('projects.admin.field.office'))->searchable()
                        ->options(fn (): array => Office::query()->with('translations')->orderBy('sort')->get()
                            ->mapWithKeys(fn (Office $o): array => [$o->id => $o->name()])->all()),
                    Grid::make(2)->schema([
                        DatePicker::make('starts_on')->label(__('projects.admin.field.starts_on')),
                        DatePicker::make('ends_on')->label(__('projects.admin.field.ends_on'))->afterOrEqual('starts_on'),
                    ]),
                    TextInput::make('budget')->label(__('projects.admin.field.budget'))->numeric()->minValue(0)->suffix('ETB'),
                    TextInput::make('spent')->label(__('projects.admin.field.spent'))->numeric()->minValue(0)->suffix('ETB')
                        ->helperText(__('projects.admin.help.spent')),
                    Toggle::make('budget_public')->label(__('projects.admin.field.budget_public')),
                    Toggle::make('featured')->label(__('projects.admin.field.featured'))->helperText(__('projects.admin.help.featured')),
                ]),
                Section::make(__('projects.admin.section.media'))->schema([
                    MediaPicker::make('cover_id', [MediaKind::Image])->label(__('admin.posts.field.featured_image')),
                    Select::make('gallery_id')->label(__('projects.admin.field.gallery'))->searchable()
                        ->options(fn (): array => Gallery::query()->with('translations')->latest()->limit(300)->get()
                            ->mapWithKeys(fn (Gallery $g): array => [$g->id => $g->title()])->all()),
                    Grid::make(2)->schema([
                        TextInput::make('latitude')->label(__('admin.offices.field.latitude'))->numeric()->minValue(-90)->maxValue(90),
                        TextInput::make('longitude')->label(__('admin.offices.field.longitude'))->numeric()->minValue(-180)->maxValue(180),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'sector.translations', 'pillar.translations']))
            ->columns([
                TextColumn::make('title')->label(__('admin.posts.field.title'))
                    ->state(fn (Project $record): string => $record->title())->wrap(),
                TextColumn::make('status')->label(__('projects.admin.field.status'))->badge()
                    ->formatStateUsing(fn (ProjectStatus $state): string => $state->label()),
                TextColumn::make('rag_status')->label(__('projects.admin.field.rag'))->badge()->placeholder('—')
                    ->formatStateUsing(fn (RagStatus $state): string => $state->label())
                    ->color(fn (RagStatus $state): string => $state->color()),
                TextColumn::make('progress')->label(__('projects.admin.field.progress'))->suffix('%')->placeholder('—')->sortable(),
                TextColumn::make('sector')->label(__('projects.admin.field.sector'))
                    ->state(fn (Project $record): ?string => $record->sector?->name())->placeholder('—'),
                IconColumn::make('featured')->label(__('projects.admin.field.featured'))->boolean(),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('projects.admin.field.status'))->options(ProjectStatus::options()),
                SelectFilter::make('rag_status')->label(__('projects.admin.field.rag'))->options(RagStatus::options()),
                SelectFilter::make('sector_id')->label(__('projects.admin.field.sector'))->options(fn (): array => self::sectorOptions()),
                SelectFilter::make('pillar_id')->label(__('admin.leaders.field.pillar'))
                    ->options(fn (): array => Pillar::query()->with('translations')->orderBy('sort')->get()
                        ->mapWithKeys(fn (Pillar $p): array => [$p->id => $p->name()])->all()),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getRelations(): array
    {
        return [KpisRelationManager::class, RisksRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Project>
     */
    public static function getEloquentQuery(): Builder
    {
        // A Project Manager scoped to a directorate sees only its projects (ADR-0024).
        $user = CurrentUser::get();
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
        $offices = OfficeScope::officeIds($user, 'projects.view');

        /** @var Builder<Project> */
        return $offices === null ? $query : $query->whereIn('office_id', $offices);
    }
}
