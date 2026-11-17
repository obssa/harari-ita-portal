<?php

namespace App\Console\Commands;

use App\Domain\Access\Enums\UserStatus;
use App\Domain\Dashboard\Support\WeeklyDigest;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Notifications\WeeklyDigestNotice;
use App\Domain\Notifications\Support\NoticePreferences;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * The weekly digest (ADR-0039): every Monday morning, each directorate head and Admin who has not switched it off
 * receives the past week and the week ahead of what they are responsible for. A person with nothing to report
 * receives nothing. `--user=` sends one person's digest now; `--dry-run` prints each recipient's sections (their
 * user id, never their address) instead of sending.
 */
final class SendWeeklyDigestCommand extends Command
{
    protected $signature = 'reports:weekly-digest {--user= : Only this user (id or email)} {--dry-run : Show who would receive what, without sending}';

    protected $description = 'Send the weekly digest to directorate heads and Admins';

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $only = $this->option('user');
        $dry = (bool) $this->option('dry-run');
        $sent = 0;

        User::query()->where('status', UserStatus::Active->value)
            ->when(is_scalar($only) && (string) $only !== '', fn ($q) => $q->where(fn ($w) => $w->where('id', (int) $only)->orWhere('email', (string) $only)))
            ->with(['roles', 'roleScopes'])
            ->chunkById(100, function ($users) use ($now, $dry, &$sent): void {
                /** @var User $user */
                foreach ($users as $user) {
                    if (! WeeklyDigest::concerns($user) || NoticePreferences::via($user, NoticeKind::Digest) === []) {
                        continue;
                    }
                    $locale = (string) ($user->locale ?: 'en');
                    $sections = WeeklyDigest::sections($user, $locale, $now);
                    if ($sections === []) {
                        continue;
                    }
                    $sent++;
                    if ($dry) {
                        $directorates = WeeklyDigest::directorates($user, $locale);
                        $this->line("User {$user->id} ({$locale}): ".($directorates === [] ? 'the whole Agency' : implode(', ', $directorates)));
                        foreach ($sections as $section) {
                            $this->line('  '.$section['title'].': '.implode('; ', $section['lines']));
                        }

                        continue;
                    }
                    $user->notify(new WeeklyDigestNotice($user->id, $now->toIso8601String()));
                }
            });

        $this->info($dry ? "Weekly digest would go to {$sent}." : "Weekly digest sent to {$sent}.");

        return self::SUCCESS;
    }
}
