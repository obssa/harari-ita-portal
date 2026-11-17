<?php

namespace App\Filament\Resources\CourseCohorts\Pages;

use App\Domain\Academy\Actions\SaveCohort;
use App\Domain\Academy\Models\Course;
use App\Domain\Academy\Models\CourseCohort;
use App\Filament\Resources\CourseCohorts\CourseCohortResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourseCohort extends CreateRecord
{
    protected static string $resource = CourseCohortResource::class;

    /** Opened from a course, the course and its usual mode are already chosen. */
    protected function fillForm(): void
    {
        parent::fillForm();
        $course = Course::query()->find(request()->query('course'));
        if ($course instanceof Course) {
            $this->form->fill([...$this->form->getRawState(), 'course_id' => $course->id, 'mode' => $course->mode->value]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveCohort::class)->handle(new CourseCohort, $data, CurrentUser::get());
    }
}
