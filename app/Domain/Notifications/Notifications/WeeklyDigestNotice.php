<?php

namespace App\Domain\Notifications\Notifications;

use App\Domain\Dashboard\Support\WeeklyDigest;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Filament\Pages\Dashboard;
use App\Models\User;
use App\Services\Calendar\EthiopianCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The weekly digest (ADR-0039) as a notice: by email with a section per area, in the bell and on Telegram as a short
 * summary, as each person chose for the "Weekly digest" kind. Built when it is sent, in the person's language.
 */
final class WeeklyDigestNotice extends StaffNotice
{
    /** @var array<string, list<array{title: string, lines: list<string>}>> sections per language, built once */
    private array $built = [];

    public function __construct(public readonly int $userId, public readonly string $weekEnding) {}

    public function kind(): NoticeKind
    {
        return NoticeKind::Digest;
    }

    public function group(): string
    {
        return 'digest';
    }

    protected function icon(): string
    {
        return 'heroicon-o-newspaper';
    }

    protected function title(string $locale): string
    {
        return __('dashboard.digest.subject', ['date' => app(EthiopianCalendar::class)->format(CarbonImmutable::parse($this->weekEnding), $locale)], $locale);
    }

    /**
     * @return list<string>
     */
    protected function lines(string $locale): array
    {
        $user = User::query()->find($this->userId);
        if ($user === null) {
            return [];
        }
        $directorates = WeeklyDigest::directorates($user, $locale);
        $intro = $directorates === []
            ? __('dashboard.digest.intro_agency', [], $locale)
            : __('dashboard.digest.intro_directorates', ['directorates' => implode(', ', $directorates)], $locale);

        return [$intro, ...array_map(fn (array $section): string => $section['title'].': '.implode('; ', $section['lines']), $this->sections($user, $locale))];
    }

    /**
     * @return list<array{title: string, lines: list<string>}>
     */
    public function sections(User $user, string $locale): array
    {
        return $this->built[$locale] ??= WeeklyDigest::sections($user, $locale, CarbonImmutable::parse($this->weekEnding));
    }

    protected function url(): string
    {
        return Dashboard::getUrl(panel: 'admin');
    }

    protected function actionLabel(string $locale): string
    {
        return __('dashboard.digest.open', [], $locale);
    }

    /** By email, a heading per section and a line per item. */
    public function toMail(User $notifiable): MailMessage
    {
        $locale = self::localeOf($notifiable);
        $message = (new MailMessage)->subject($this->title($locale))->line($this->lines($locale)[0] ?? '');
        foreach ($this->sections($notifiable, $locale) as $section) {
            $message->line('**'.$section['title'].'**');
            foreach ($section['lines'] as $line) {
                $message->line('- '.$line);
            }
        }

        return $message
            ->action($this->actionLabel($locale), $this->url())
            ->line(__('notifications.mail.choices', [], $locale));
    }
}
