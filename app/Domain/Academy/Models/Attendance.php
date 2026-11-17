<?php

namespace App\Domain\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A learner was at a session (ADR-0030): the row exists only for those present, with who marked it.
 *
 * @property int $id
 * @property int $course_session_id
 * @property string $enrolment_id
 * @property ?int $marked_by
 * @property-read CourseSession $session
 * @property-read Enrolment $enrolment
 * @property-read ?User $marker
 */
class Attendance extends Model
{
    use LogsActivity;

    protected $fillable = ['course_session_id', 'enrolment_id', 'marked_by'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('academy')->logOnly(['course_session_id', 'enrolment_id', 'marked_by'])->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<CourseSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    /**
     * @return BelongsTo<Enrolment, $this>
     */
    public function enrolment(): BelongsTo
    {
        return $this->belongsTo(Enrolment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
