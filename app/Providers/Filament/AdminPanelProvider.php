<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Controllers\Admin\PrintCohortCertificatesController;
use App\Http\Middleware\RequireMfaForPrivilegedRoles;
use App\Http\Middleware\RestrictAdminByIp;
use App\Http\Middleware\SetAdminLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /** The admin menu's groups, in order (ADR-0052). */
    public const MENU_GROUPS = [
        'admin.nav.content',
        'admin.nav.organisation',
        'admin.nav.services',
        'admin.nav.innovation',
        'admin.nav.projects_data',
        'admin.nav.academy',
        'admin.nav.opportunities',
        'admin.nav.communication',
        'admin.nav.insights',
        'translations.admin.nav',
        'admin.nav.site',
        'admin.nav.administration',
    ];

    /**
     * A public file with its modification time, so browsers fetch it again after a deploy.
     */
    private static function asset(string $path): string
    {
        $file = public_path($path);

        return '/'.$path.(is_file($file) ? '?v='.filemtime($file) : '');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(fn (): string => __('site.name'))
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            // ADR-0052: the menu follows the Agency's work: what editors publish, the Agency, its four modules, then
            // communication, insight, languages, the site's setup and administration. Labels resolve per request, so
            // the groups match in every admin language.
            ->navigationGroups(array_map(
                fn (string $key): NavigationGroup => NavigationGroup::make()->label(fn (): string => __($key)),
                self::MENU_GROUPS,
            ))
            ->colors([
                // HinTA teal 700 (resources/css/tokens.css, ADR-0007/0015).
                'primary' => Color::hex('#105D6F'),
            ])
            ->favicon('/favicon-96.png?v=2')
            // ADR-0015: HinTA's brand in the admin; fonts served from this site.
            ->brandLogo(fn (): View => view('filament.brand'))
            ->brandLogoHeight('2.5rem')
            ->font('Inter Variable', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): HtmlString => new HtmlString(
                '<link rel="stylesheet" href="'.e(self::asset('brand/admin.css')).'">'
            ))
            // Authenticator-app MFA for everyone who wants it; required for the
            // roles in config('portal.mfa_required_roles') by our middleware.
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName((string) config('app.name'))],
                isRequired: true,
            )
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireMfaForPrivilegedRoles::class)
            // Deny by default: a resource without a policy method fails closed.
            ->strictAuthorization()
            // In-app notifications from the editorial workflow (ADR-0013).
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            // Initials drawn locally: no staff names sent to a third party, CSP-safe.
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // ADR-0030: a cohort's certificates on one printable page, for staff who issue them.
            ->authenticatedRoutes(function (): void {
                Route::get('academy/cohorts/{cohort}/certificates/print', PrintCohortCertificatesController::class)
                    ->whereNumber('cohort')->name('academy.certificates.print');
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // ADR-0038: optional allow-list of addresses, also for the sign-in page and Livewire requests.
            ->middleware([RestrictAdminByIp::class], isPersistent: true)
            // Persistent: also applies to Livewire requests made from admin pages.
            ->authMiddleware([
                Authenticate::class,
                SetAdminLocale::class,
            ], isPersistent: true);
    }
}
