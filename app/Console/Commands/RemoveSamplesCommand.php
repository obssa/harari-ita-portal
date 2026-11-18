<?php

namespace App\Console\Commands;

use App\Domain\Health\Support\LaunchCheck;
use Illuminate\Console\Command;

/**
 * Removes every set of labelled sample content in one step before the launch (ADR-0042): each sample command removes
 * exactly what it added. Run it before APP_ENV becomes production, as the sample commands refuse to run there.
 */
final class RemoveSamplesCommand extends Command
{
    /** The sample commands, each with --remove. */
    public const COMMANDS = [
        'portal:sample-opportunities', 'portal:sample-academy', 'portal:sample-projects-data', 'portal:sample-innovation',
        'portal:sample-services', 'portal:sample-post', 'portal:sample-content',
    ];

    protected $signature = 'portal:remove-samples {--force : Do not ask for confirmation}';

    protected $description = 'Remove all labelled sample content (before the launch)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Not in production: remove the samples before switching to production.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Remove all labelled sample content?')) {
            return self::SUCCESS;
        }
        foreach (self::COMMANDS as $command) {
            $this->components->task($command, fn (): bool => $this->callSilently($command, ['--remove' => true]) === self::SUCCESS);
        }

        $left = LaunchCheck::samples();
        $this->line($left === [] ? 'No labelled sample content is left.' : 'Still labelled as samples: '.json_encode($left));

        return $left === [] ? self::SUCCESS : self::FAILURE;
    }
}
