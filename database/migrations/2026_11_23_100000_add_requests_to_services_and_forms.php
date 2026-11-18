<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0053: every service can be requested from its own page. A service says whether it takes requests and,
 * optionally, which form asks its own questions; a form submission remembers what it is about.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_items', function (Blueprint $table): void {
            $table->boolean('accepts_requests')->default(true)->after('external_url');
            $table->foreignUlid('request_form_id')->nullable()->after('accepts_requests')->constrained('forms')->nullOnDelete();
        });

        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->string('about_type', 40)->nullable()->after('form_id');
            $table->ulid('about_id')->nullable()->after('about_type');
            $table->index(['about_type', 'about_id']);
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropIndex(['about_type', 'about_id']);
            $table->dropColumn(['about_type', 'about_id']);
        });

        Schema::table('service_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('request_form_id');
            $table->dropColumn('accepts_requests');
        });
    }
};
