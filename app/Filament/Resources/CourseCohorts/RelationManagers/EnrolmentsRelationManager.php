<?php

namespace App\Filament\Resources\CourseCohorts\RelationManagers;

use App\Domain\Academy\Actions\CancelEnrolment;
use App\Domain\Academy\Actions\IssueCertificate;
use App\Domain\Academy\Enums\EnrolmentStatus;
use App\Domain\Academy\Models\CourseCohort;
use App\Domain\Academy\Models\Enrolment;
use App\Domain\Academy\Support\AttendanceRule;
use App\Domain\Translation\Support\Locales;
use App\Filament\Support\CalendarDate;
use App\Filament\Support\CurrentUser;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * A cohort's learners (ADR-0029, ADR-0030): status, sessions attended against the rule, and the certificate. Staff
 * who manage cohorts may give a place back for a learner; those who issue certificates may issue one.
 */
class EnrolmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrolments';

    /** @var array<string, int>|null */
    private ?array $attended = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('academy.admin.learners');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof CourseCohort) {
            return null;
        }
        $count = $ownerRecord->enrolledCount();

        return $ownerRecord->capacity !== null ? "{$count} / {$ownerRecord->capacity}" : (string) $count;
    }

    public function table(Table $table): Table
    {
        /** @var CourseCohort $cohort */
        $cohort = $this->getOwnerRecord();
        $cohort->loadMissing('course');
        $sessions = $cohort->sessions()->count();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('erased_at')->with('certificate'))
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('name')->label(__('academy.field.name')),
                TextColumn::make('phone')->label(__('academy.field.phone'))->placeholder('—'),
                TextColumn::make('email')->label(__('academy.field.email'))->placeholder('—')->toggleable(),
                TextColumn::make('status')->label(__('academy.admin.status'))->badge()
                    ->formatStateUsing(fn (EnrolmentStatus $state): string => $state->label())
                    ->color(fn (EnrolmentStatus $state): string => $state->color()),
                TextColumn::make('attended')->label(__('academy.admin.attended'))
                    ->state(function (Enrolment $record) use ($sessions, $cohort): string {
                        $attended = $this->attended($cohort)[$record->id] ?? 0;
                        $percent = AttendanceRule::percent($sessions, $attended);

                        return $percent === null ? '—' : "{$attended} / {$sessions} ({$percent}%)";
                    })
                    ->color(fn (Enrolment $record): ?string => AttendanceRule::meets($cohort->course->attendance_required, $sessions, $this->attended($cohort)[$record->id] ?? 0) ? 'success' : null),
                TextColumn::make('certificate.code')->label(__('academy.admin.certificate'))->placeholder('—')->copyable()->fontFamily('mono'),
                TextColumn::make('locale')->label(__('admin.revisions.field.language'))
                    ->formatStateUsing(fn (string $state): string => Locales::nativeName($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('academy.admin.enrolled_at'))->formatStateUsing(CalendarDate::format(withTime: true))->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('academy.admin.status'))->options(EnrolmentStatus::options()),
            ])
            ->recordActions([
                Action::make('issue')->label(__('academy.admin.issue'))->icon(Heroicon::OutlinedCheckBadge)
                    ->visible(fn (Enrolment $record): bool => CurrentUser::get()->can('issueCertificates', $cohort) && $record->status === EnrolmentStatus::Enrolled
                        && $record->certificate === null && $cohort->starts_on <= CarbonImmutable::today())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Enrolment $record): string => AttendanceRule::meets($cohort->course->attendance_required, $sessions, $this->attended($cohort)[$record->id] ?? 0)
                        ? __('academy.admin.issue_help') : __('academy.admin.issue_below_rule', ['percent' => $cohort->course->attendance_required]))
                    ->action(function (Enrolment $record): void {
                        try {
                            $certificate = app(IssueCertificate::class)->handle($record, CurrentUser::get());
                            Notification::make()->success()->title(__('academy.admin.issued_one', ['code' => $certificate->code]))->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title((string) collect($e->errors())->flatten()->first())->send();
                        }
                    }),
                Action::make('cancel')->label(__('academy.admin.cancel_place'))->icon(Heroicon::OutlinedXCircle)->color('danger')
                    ->visible(fn (Enrolment $record): bool => CurrentUser::get()->can('update', $record) && $record->status !== EnrolmentStatus::Cancelled && $record->certificate === null)
                    ->requiresConfirmation()->modalDescription(__('academy.admin.cancel_place_help'))
                    ->action(function (Enrolment $record): void {
                        app(CancelEnrolment::class)->handle($record);
                        Notification::make()->success()->title(__('academy.admin.place_cancelled'))->send();
                    }),
            ]);
    }

    /**
     * @return array<string, int>
     */
    private function attended(CourseCohort $cohort): array
    {
        return $this->attended ??= AttendanceRule::attendedCounts($cohort);
    }
}
