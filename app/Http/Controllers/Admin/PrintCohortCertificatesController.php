<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Academy\Models\CourseCohort;
use App\Filament\Support\CurrentUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * A cohort's standing certificates on one page, one sheet each, for staff to print (ADR-0030). Each sheet is in the
 * language its learner enrolled in. The page is never cached and is recorded in the audit log.
 */
final class PrintCohortCertificatesController extends Controller
{
    public function __invoke(int $cohort): Response
    {
        $record = CourseCohort::query()->with(['course.translations'])->findOrFail($cohort);
        $actor = CurrentUser::get();
        abort_unless($actor->can('issueCertificates', $record), 403);

        $certificates = $record->certificates()->whereNull('revoked_at')->with(['enrolment', 'course.translations', 'cohort'])
            ->orderBy('issued_at')->get();
        activity('academy')->performedOn($record)->causedBy($actor)->event('printed')
            ->withProperties(['certificates' => $certificates->count()])->log('Certificates printed');

        return response()->view('pages.academy.print', [
            'title' => $record->label(),
            'certificates' => $certificates,
        ])->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
