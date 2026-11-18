<?php

namespace App\Filament\Resources\Services;

use App\Domain\Content\Models\Category;
use App\Domain\Forms\Models\Form;
use App\Domain\Organisation\Models\Office;
use App\Domain\Services\Enums\ServiceAudience;
use App\Domain\Services\Enums\ServiceChannel;
use App\Domain\Services\Models\ServiceItem;
use App\Filament\Resources\Services\Pages\CreateServiceItem;
use App\Filament\Resources\Services\Pages\EditServiceItem;
use App\Filament\Resources\Services\Pages\ListServiceItems;
use App\Filament\Support\DirectoryFields;
use App\Filament\Support\SentenceCaseLabels;
use App\Filament\Support\Translatable;
use App\Filament\Support\ViewOnSite;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
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

/**
 * The services catalogue in the admin (ADR-0016): a tab per language with the words, the details beside them.
 */
class ServiceItemResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = ServiceItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'services';

    public static function getModelLabel(): string
    {
        return __('admin.services.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.services.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Translatable::tabs(fn (string $locale): array => [
                    Translatable::mainField($locale, 'title', 200)->label(__('admin.services.field.title')),
                    Translatable::slugField($locale, 'service_item_translations'),
                    Textarea::make("translations.{$locale}.summary")
                        ->label(__('admin.services.field.summary'))
                        ->rows(2)
                        ->maxLength(400),
                    Textarea::make("translations.{$locale}.eligibility")
                        ->label(__('admin.services.field.eligibility'))
                        ->rows(3)
                        ->maxLength(2000),
                    Textarea::make("translations.{$locale}.documents")
                        ->label(__('admin.services.field.documents'))
                        ->helperText(__('admin.services.help.one_per_line'))
                        ->rows(4)
                        ->maxLength(3000),
                    Textarea::make("translations.{$locale}.steps")
                        ->label(__('admin.services.field.steps'))
                        ->helperText(__('admin.services.help.one_per_line'))
                        ->rows(5)
                        ->maxLength(4000),
                    Grid::make(2)->schema([
                        TextInput::make("translations.{$locale}.fees")
                            ->label(__('admin.services.field.fees'))
                            ->placeholder(__('admin.services.help.fees'))
                            ->maxLength(300),
                        TextInput::make("translations.{$locale}.duration")
                            ->label(__('admin.services.field.duration'))
                            ->placeholder(__('admin.services.help.duration'))
                            ->maxLength(200),
                    ]),
                    DirectoryFields::body($locale, __('admin.services.field.body')),
                    Repeater::make("translations.{$locale}.faq")
                        ->label(__('admin.services.field.faq'))
                        ->schema([
                            TextInput::make('question')->label(__('admin.services.field.question'))->required()->maxLength(300),
                            Textarea::make('answer')->label(__('admin.services.field.answer'))->required()->rows(2)->maxLength(2000),
                        ])
                        ->maxItems(12)
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel(__('admin.services.field.add_question')),
                    DirectoryFields::published($locale, 'services.manage'),
                ]),
            ]),
            Section::make(__('admin.posts.section.details'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                CheckboxList::make('channels')
                    ->label(__('admin.services.field.channels'))
                    ->options(ServiceChannel::options())
                    ->required(),
                TextInput::make('external_url')
                    ->label(__('admin.services.field.external_url'))
                    ->helperText(__('admin.services.help.external_url'))
                    ->url()
                    ->startsWith(['https://'])
                    ->maxLength(500),
                Toggle::make('accepts_requests')
                    ->label(__('admin.services.field.accepts_requests'))
                    ->helperText(__('admin.services.help.accepts_requests'))
                    ->default(true),
                Select::make('request_form_id')
                    ->label(__('admin.services.field.request_form'))
                    ->helperText(__('admin.services.help.request_form'))
                    ->searchable()
                    ->options(fn (): array => Form::query()->with('translations')->where('active', true)->get()
                        ->mapWithKeys(fn (Form $form): array => [$form->id => $form->title()])->all())
                    ->visible(fn (Get $get): bool => (bool) $get('accepts_requests')),
                CheckboxList::make('audiences')
                    ->label(__('admin.services.field.audiences'))
                    ->options(ServiceAudience::options()),
                Select::make('category_id')
                    ->label(__('admin.services.field.category'))
                    ->options(fn (): array => self::categoryOptions())
                    ->helperText(__('admin.services.help.category')),
                Select::make('office_id')
                    ->label(__('admin.services.field.office'))
                    ->searchable()
                    ->options(fn (): array => Office::query()->with('translations')->orderBy('type')->orderBy('sort')->get()
                        ->mapWithKeys(fn (Office $office): array => [$office->id => $office->name()])->all()),
                Toggle::make('proposed')->label(__('admin.services.field.proposed'))->helperText(__('admin.services.help.proposed')),
                Toggle::make('featured')->label(__('admin.services.field.featured'))->helperText(__('admin.services.help.featured')),
                TextInput::make('rank')->label(__('admin.services.field.rank'))->numeric()->integer()->minValue(0)->default(0),
                DatePicker::make('reviewed_at')->label(__('admin.services.field.reviewed_at'))->native(false)->maxDate(now()),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('rank')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['translations', 'office.translations', 'category.translations']))
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.services.field.title'))
                    ->state(fn (ServiceItem $record): string => $record->title())
                    ->description(fn (ServiceItem $record): ?string => $record->office?->name()),
                TextColumn::make('channels')
                    ->label(__('admin.services.field.channels'))
                    ->state(fn (ServiceItem $record): array => array_map(fn (ServiceChannel $c): string => $c->label(), $record->channelList()))
                    ->badge(),
                IconColumn::make('proposed')->label(__('admin.services.field.proposed'))->boolean(),
                IconColumn::make('featured')->label(__('admin.services.field.featured'))->boolean(),
                TextColumn::make('reviewed_at')->label(__('admin.services.field.reviewed_at'))->date()->placeholder('—'),
                DirectoryFields::languagesColumn(),
            ])
            ->filters([
                SelectFilter::make('office_id')
                    ->label(__('admin.services.field.office'))
                    ->options(fn (): array => Office::query()->with('translations')->get()->mapWithKeys(fn (Office $o): array => [$o->id => $o->name()])->all()),
                SelectFilter::make('category_id')->label(__('admin.services.field.category'))->options(fn (): array => self::categoryOptions()),
                SelectFilter::make('proposed')->label(__('admin.services.field.proposed'))->options(['1' => __('admin.services.filter.proposed'), '0' => __('admin.services.filter.confirmed')]),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewOnSite::row(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return Category::query()->with('translations')->where('type', Category::TYPE_SERVICE)->orderBy('sort')->get()
            ->mapWithKeys(fn (Category $category): array => [$category->id => $category->name()])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceItems::route('/'),
            'create' => CreateServiceItem::route('/create'),
            'edit' => EditServiceItem::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<ServiceItem>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<ServiceItem> */
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
