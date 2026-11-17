<?php

namespace App\Filament\Resources\CourseCohorts\RelationManagers;

use App\Domain\Academy\Actions\MarkAttendance;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\CourseSession;
use App\Domain\Academy\Models\Enrolment;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * A cohort's sessions with attendance (ADR-0030): each session opens a checklist of the enrolled learners; ticking
 * those present and saving records them all at once. Sessions themselves are planned in the cohort's form.
 */
class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('academy.admin.attendance');
    }

    /** Attendance is marked from the read-only cohort page too. */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var CourseCohort $cohort */
        $cohort = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('attendances'))
            ->defaultSort('starts_at')
            ->paginated(false)
            ->columns([
                TextColumn::make('starts_at')->label(__('academy.field.session_starts'))->formatStateUsing(CalendarDate::format(withTime: true)),
                TextColumn::make('title')->label(__('academy.field.session_title'))->state(fn (CourseSession $record): string => $record->title())->placeholder('—'),
                TextColumn::make('attendances_count')->label(__('academy.admin.present'))
                    ->formatStateUsing(fn (int $state): string => $state.' / '.$cohort->enrolledCount()),
            ])
            ->recordActions([
                Action::make('attendance')->label(__('academy.admin.mark_attendance'))->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->visible(fn (CourseSession $record): bool => CurrentUser::get()->can('attendance', $cohort) && $record->starts_at->startOfDay() <= CarbonImmutable::now())
                    ->modalHeading(fn (CourseSession $record): string => __('academy.admin.attendance_for', ['date' => (string) CalendarDate::format(withTime: true)($record->starts_at)]))
                    ->fillForm(fn (CourseSession $record): array => ['present' => $record->attendances()->pluck('enrolment_id')->all()])
                    ->schema([
                        CheckboxList::make('present')->label(__('academy.admin.present'))->bulkToggleable()->searchable()->columns(2)
                            ->options(fn (): array => $cohort->enrolments()->where('status', EnrolmentStatus::Enrolled->value)->whereNull('erased_at')
                                ->orderBy('created_at')->get()->mapWithKeys(fn (Enrolment $e): array => [$e->id => (string) $e->name])->sort()->all()),
                    ])
                    ->action(function (CourseSession $record, array $data): void {
                        try {
                            $count = app(MarkAttendance::class)->handle($record, array_values((array) ($data['present'] ?? [])), CurrentUser::get());
                            Notification::make()->success()->title(trans_choice('academy.admin.marked', $count, ['count' => $count]))->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                        }
                    }),
            ]);
    }
}
