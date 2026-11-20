<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0054: the "translating" content status is gone, as nothing could set it. Any language still marked so (none on
 * staging when this was written) becomes a draft, which is what it was in every workflow rule.
 */
return new class extends Migration
{
    private const TABLES = [
        'course_translations', 'page_translations', 'post_translations', 'document_translations', 'event_translations',
        'form_translations', 'challenge_translations', 'programme_translations', 'startup_translations',
        'gallery_translations', 'video_translations', 'dataset_translations', 'opportunity_translations',
        'leader_translations', 'office_translations', 'pillar_translations', 'project_translations',
        'service_item_translations',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::table($table)->where('status', 'translating')->update(['status' => 'draft']);
            }
        }
    }

    public function down(): void
    {
        // Nothing to restore: no language can be told apart as having been "translating".
    }
};
