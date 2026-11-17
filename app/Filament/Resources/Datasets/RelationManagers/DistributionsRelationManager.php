<?php

namespace App\Filament\Resources\Datasets\RelationManagers;

use App\Domain\Media\Enums\MediaKind;
use App\Domain\OpenData\Actions\SaveDistribution;
use App\Domain\OpenData\Enums\DistributionFormat;
use App\Domain\OpenData\Models\Dataset;
use App\Domain\OpenData\Models\DatasetDistribution;
use App\Domain\Translation\Support\Locales;
use App\Filament\Support\CurrentUser;
use App\Filament\Support\MediaPicker;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/**
 * A dataset's files and links (ADR-0025). Saving a file works out its size, checksum, rows, columns and preview;
 * column names that look like personal data are flagged here before the dataset is published.
 */
class DistributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'distributions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('opendata.admin.distributions');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('format')->label(__('opendata.field.format'))->options(DistributionFormat::options())->required()->live(),
            MediaPicker::make('media_id', [MediaKind::Document])->label(__('opendata.field.file'))
                ->helperText(__('opendata.admin.help.file'))
                ->visible(fn (Get $get): bool => DistributionFormat::tryFrom((string) $get('format'))?->isFile() ?? true)
                ->required(fn (Get $get): bool => DistributionFormat::tryFrom((string) $get('format'))?->isFile() ?? false),
            TextInput::make('url')->label(__('opendata.field.api_url'))->url()->maxLength(500)
                ->visible(fn (Get $get): bool => $get('format') === DistributionFormat::Api->value)
                ->required(fn (Get $get): bool => $get('format') === DistributionFormat::Api->value),
            Section::make(__('opendata.field.distribution_title'))->description(__('opendata.admin.help.distribution_title'))->collapsed()->schema([
                Grid::make(2)->schema(array_map(
                    fn (string $locale): TextInput => TextInput::make("titles.{$locale}")->label(Locales::nativeName($locale))->maxLength(120),
                    Locales::all(),
                )),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Dataset $dataset */
        $dataset = $this->getOwnerRecord();

        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('format')->label(__('opendata.field.format'))->badge()
                    ->formatStateUsing(fn (DistributionFormat $state): string => $state->code()),
                TextColumn::make('title')->label(__('admin.posts.field.title'))->state(fn (DatasetDistribution $record): string => $record->title())->wrap(),
                TextColumn::make('row_count')->label(__('opendata.field.rows'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : (string) Number::format($state, locale: 'en'))->placeholder('—'),
                TextColumn::make('byte_size')->label(__('opendata.field.size'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : Number::fileSize($state, precision: 1))->placeholder('—'),
                TextColumn::make('personal_columns')->label(__('opendata.admin.personal'))
                    ->state(fn (DatasetDistribution $record): ?string => $record->personal_columns ? implode(', ', $record->personal_columns) : null)
                    ->color('danger')->icon(fn (DatasetDistribution $record): ?Heroicon => $record->personal_columns ? Heroicon::OutlinedExclamationTriangle : null)
                    ->placeholder('—')->wrap(),
                TextColumn::make('download_count')->label(__('opendata.field.downloads')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('opendata.admin.add_distribution'))
                    ->using(fn (array $data): DatasetDistribution => app(SaveDistribution::class)->handle($dataset, new DatasetDistribution, $data, CurrentUser::get())),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (DatasetDistribution $record, array $data): DatasetDistribution => app(SaveDistribution::class)->handle($dataset, $record, $data, CurrentUser::get())),
                Action::make('remove')->label(__('opendata.admin.remove_distribution'))->icon(Heroicon::OutlinedTrash)->color('danger')
                    ->visible(fn (DatasetDistribution $record): bool => CurrentUser::get()->can('delete', $record))
                    ->requiresConfirmation()
                    ->action(fn (DatasetDistribution $record) => app(SaveDistribution::class)->remove($record, CurrentUser::get())),
            ]);
    }
}
