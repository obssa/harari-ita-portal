<?php

namespace App\Http\Middleware;

use App\Domain\Translation\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps every non-production environment (staging at hita.orcamind.dev,
 * local, testing) out of search engines, so test content is never indexed.
 */
final class NoIndexOutsideProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // ADR-0043: a language hidden from visitors is not indexed either.
        if (! app()->environment('production') || Locales::isHidden(app()->getLocale())) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
