<?php

use App\Domain\Innovation\Enums\ApplicationStage;
use App\Domain\Innovation\Enums\ListingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0054: the innovation retention period counts from when a decision was taken, not from the last edit. A
 * listing keeps the time of its latest decision; withdrawn applications and decisions taken before this change are
 * dated from their last edit, which is the best date they have.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table): void {
            $table->timestamp('decided_at')->nullable()->after('listed_at');
        });

        DB::table('startups')->whereIn('listing_status', [ListingStatus::Declined->value, ListingStatus::Archived->value])
            ->update(['decided_at' => DB::raw('updated_at')]);
        DB::table('applications')->whereNull('decided_at')
            ->whereIn('stage', [ApplicationStage::Accepted->value, ApplicationStage::Waitlisted->value, ApplicationStage::Declined->value, ApplicationStage::Withdrawn->value])
            ->update(['decided_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table): void {
            $table->dropColumn('decided_at');
        });
    }
};
