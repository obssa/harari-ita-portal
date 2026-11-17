<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Domain\Academy\Actions\SaveCourse;
use App\Domain\Academy\Models\Course;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Support\CurrentUser;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveCourse::class)->handle(new Course, $data, CurrentUser::get());
    }

    /** Cohorts are added on the next page. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
