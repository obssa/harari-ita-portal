<?php

namespace App\Filament\Resources\Instructors;

use App\Domain\Academy\Actions\SaveInstructor;
use App\Domain\Academy\Models\Instructor;
use App\Domain\Translation\Support\Locales;
use App\Filament\Resources\Instructors\Pages\ManageInstructors;
use App\Filament\Support\CurrentUser;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Instructors (ADR-0028): their name, title per language and, for staff, the account through which they mark
 * attendance. They appear on public pages only with recorded consent.
 */
class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 52;

    public static function getModelLabel(): string
    {
        return __('academy.admin.instructor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy.admin.instructors');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.academy');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label(__('academy.field.name'))->required()->maxLength(120),
            Section::make(__('academy.field.instructor_title'))->collapsed()->schema([
                Grid::make(2)->schema(array_map(
                    fn (string $locale): TextInput => TextInput::make("titles.{$locale}")->label(Locales::nativeName($locale))->maxLength(120),
                    Locales::all(),
                )),
            ]),
            Select::make('user_id')->label(__('academy.field.account'))->searchable()
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->helperText(__('academy.admin.help.account')),
            Toggle::make('consented')->label(__('academy.field.consented'))->helperText(__('academy.admin.help.consented'))->live(),
            Toggle::make('public')->label(__('academy.field.public'))->helperText(__('academy.admin.help.public')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->with('user')->withCount('cohorts'))
            ->columns([
                TextColumn::make('name')->label(__('academy.field.name'))->searchable(),
                TextColumn::make('title')->label(__('academy.field.instructor_title'))->state(fn (Instructor $record): string => $record->title())->placeholder('—'),
                TextColumn::make('user.name')->label(__('academy.field.account'))->placeholder('—'),
                TextColumn::make('cohorts_count')->label(__('academy.admin.cohorts')),
                IconColumn::make('shown')->label(__('academy.field.public'))->boolean()->state(fn (Instructor $record): bool => $record->isShown()),
            ])
            ->headerActions([])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Instructor $record): array => [...$data, 'consented' => $record->consented_at !== null])
                    ->using(fn (Instructor $record, array $data): Instructor => app(SaveInstructor::class)->handle($record, $data, CurrentUser::get())),
                DeleteAction::make(),
            ]);
    }

    public static function createAction(): CreateAction
    {
        return CreateAction::make()->using(fn (array $data): Instructor => app(SaveInstructor::class)->handle(new Instructor, $data, CurrentUser::get()));
    }

    public static function getPages(): array
    {
        return ['index' => ManageInstructors::route('/')];
    }
}
