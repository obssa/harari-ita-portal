<?php

namespace App\View\Components;

use App\Domain\Translation\Support\Locales;
use App\Http\Support\LocalizedUrls;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * hreflang alternates for every language, x-default and the canonical URL
 * (PLAN.md §11 SEO).
 */
final class LocaleMeta extends Component
{
    /** @var array<string, string> */
    public array $urls;

    public ?string $default;

    public string $canonical;

    /**
     * @param  array<string, string>|null  $alternates  per-language URLs when slugs differ
     */
    public function __construct(?array $alternates = null)
    {
        // ADR-0043: hidden languages are left out of hreflang.
        $this->urls = array_filter($alternates ?? LocalizedUrls::forCurrentRoute(), fn (string $locale): bool => ! Locales::isHidden($locale), ARRAY_FILTER_USE_KEY);
        $this->default = $this->urls[Locales::default()] ?? null;
        $this->canonical = $this->urls[app()->getLocale()] ?? url()->current();
    }

    public function render(): View
    {
        return view('components.locale-meta');
    }
}
