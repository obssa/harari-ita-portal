<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The homepage's values section was seeded with the source project's seven core values on the first deploy.
 * They are not the Agency's: empty the list where it still holds exactly those, so editors' own values stay.
 */
return new class extends Migration
{
    private const INHERITED = ['Medemer', 'Multinational unity', 'Democracy', 'National sovereignty', 'Inclusiveness', 'Patriotism', 'Solidarity'];

    public function up(): void
    {
        foreach (DB::table('homepage_sections')->where('key', 'values')->get() as $row) {
            $settings = json_decode((string) $row->settings, true);
            if (! is_array($settings)) {
                continue;
            }
            $titles = array_map(fn ($item): ?string => is_array($item) ? ($item['title']['en'] ?? null) : null, (array) ($settings['items'] ?? []));
            if ($titles === self::INHERITED) {
                $settings['items'] = [];
                DB::table('homepage_sections')->where('id', $row->id)->update(['settings' => json_encode($settings), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to restore: the inherited values were never the Agency's.
    }
};
