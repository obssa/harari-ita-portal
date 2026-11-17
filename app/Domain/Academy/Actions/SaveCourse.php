<?php

namespace App\Domain\Academy\Actions;

use App\Domain\Academy\Enums\CourseLevel;
use App\Domain\Academy\Enums\CourseMode;
use App\Domain\Academy\Models\Course;
use App\Domain\Content\Actions\WriteTranslations;
use App\Domain\Media\Actions\SyncMediaUsage;
use App\Domain\Organisation\Models\Pillar;
use App\Domain\Status\Support\LocalisedText;
use App\Domain\Translation\Support\Locales;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a course with its languages (ADR-0028): level, usual mode, length, the languages it is taught in, pillar,
 * partner, cover, the attendance a certificate needs (50% to 100%) and the signatory. Only those with
 * `courses.manage` may publish a language.
 */
final class SaveCourse
{
    public const TEXT_FIELDS = ['summary', 'audience', 'prerequisites', 'outcomes', 'syllabus'];

    public function __construct(
        private readonly WriteTranslations $translations,
        private readonly SyncMediaUsage $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Course $course, array $data, User $actor): Course
    {
        /** @var array<string, array<string, mixed>> $translations */
        $translations = (array) ($data['translations'] ?? []);
        WriteTranslations::requireName($translations, 'title');

        $pillar = filled($data['pillar_id'] ?? null) && Pillar::query()->whereKey($data['pillar_id'])->exists() ? (string) $data['pillar_id'] : null;
        $languages = array_values(array_intersect(Locales::all(), array_map('strval', (array) ($data['languages'] ?? []))));
        $hours = filled($data['duration_hours'] ?? null) ? min(2000, max(1, (int) $data['duration_hours'])) : null;
        $attendance = filled($data['attendance_required'] ?? null) ? min(100, max(50, (int) $data['attendance_required'])) : Course::DEFAULT_ATTENDANCE;
        $signatory = trim((string) ($data['signatory_name'] ?? ''));
        $titles = LocalisedText::clean($data['signatory_titles'] ?? [], 120);

        return DB::transaction(function () use ($course, $data, $translations, $actor, $pillar, $languages, $hours, $attendance, $signatory, $titles): Course {
            $course->fill([
                'pillar_id' => $pillar,
                'level' => CourseLevel::tryFrom((string) ($data['level'] ?? '')) ?? CourseLevel::Beginner,
                'mode' => CourseMode::tryFrom((string) ($data['mode'] ?? '')) ?? CourseMode::InPerson,
                'duration_hours' => $hours,
                'languages' => $languages === [] ? null : $languages,
                'partner' => filled($data['partner'] ?? null) ? mb_substr(trim((string) $data['partner']), 0, 200) : null,
                'cover_image_id' => filled($data['cover_image_id'] ?? null) ? (int) $data['cover_image_id'] : null,
                'attendance_required' => $attendance,
                'signatory_name' => $signatory !== '' ? mb_substr($signatory, 0, 120) : null,
                'signatory_titles' => $titles === [] ? null : $titles,
                'featured' => (bool) ($data['featured'] ?? false),
                'sort' => max(0, (int) ($data['sort'] ?? 0)),
            ]);
            if (! $course->exists) {
                $course->created_by = $actor->id;
            }
            $course->updated_by = $actor->id;
            $course->save();

            $this->translations->handle($course, $translations, self::TEXT_FIELDS, $actor->can('courses.manage'), 'title');
            $this->usage->handle($course, 'cover_image', [$course->cover_image_id]);

            return $course->refresh();
        });
    }
}
