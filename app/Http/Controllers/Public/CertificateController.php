<?php

namespace App\Http\Controllers\Public;

use App\Domain\Academy\Models\Certificate;
use App\Domain\Academy\Models\CourseTranslation;
use App\Domain\Organisation\Queries\PublicDirectory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checking a certificate by its code or QR code (ADR-0030): valid, revoked or not found. A valid one shows the
 * holder's initials, the course, the cohort's dates and the issue date, never the full name. Rate limited, never
 * cached (a revocation shows at once) and not indexed.
 */
final class CertificateController extends Controller
{
    public function form(Request $request): Response|RedirectResponse
    {
        if ($request->filled('code')) {
            $code = Certificate::normaliseCode((string) $request->query('code'));
            if ($code !== '') {
                return redirect()->route('certificates.verify.show', ['code' => $code]);
            }

            return $this->page(['typed' => (string) $request->query('code'), 'invalid' => true], 422);
        }

        return $this->page([]);
    }

    public function show(string $code): Response
    {
        $certificate = Certificate::query()->where('code', $code)->with(['course.translations', 'cohort'])->first();
        $state = match (true) {
            $certificate === null => 'unknown',
            $certificate->isValid() => 'valid',
            default => 'revoked',
        };
        $translation = $certificate !== null ? PublicDirectory::shown($certificate->course, app()->getLocale()) : null;

        return $this->page([
            'code' => $code,
            'state' => $state,
            'certificate' => $state === 'valid' ? $certificate : null,
            'courseTitle' => $certificate !== null && $state === 'valid'
                ? ($translation instanceof CourseTranslation ? $translation->title : $certificate->course->title())
                : null,
        ], $state === 'unknown' ? 404 : 200);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function page(array $data, int $status = 200): Response
    {
        return response()->view('pages.academy.verify', [...['code' => null, 'state' => null, 'certificate' => null, 'courseTitle' => null, 'typed' => null, 'invalid' => false], ...$data], $status)
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
