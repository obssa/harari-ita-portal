<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The startups directory (PLAN.md §4, §5; ADR-0020): startups and innovators of the region, listed after review by
 * the Agency, with their pitch per language and the startup-label claim (self-declared, or verified by the Agency
 * against the Ministry's certificate). The founder's contact is encrypted and never public.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('reference', 16)->unique();
            $table->foreignUlid('sector_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('stage', 24);
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->unsignedSmallInteger('team_size')->nullable();
            $table->string('area', 120)->nullable();
            $table->string('website', 300)->nullable();
            $table->foreignId('logo_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('label_status', 16)->default('none');
            $table->timestamp('label_verified_at')->nullable();
            $table->foreignId('label_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('listing_status', 24)->default('submitted');
            $table->text('review_note')->nullable();
            $table->text('founder_name')->nullable();
            $table->text('founder_email')->nullable();
            $table->text('founder_phone')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->char('phone_hash', 64)->nullable()->index();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('listed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'listing_status']);
            $table->index(['site_id', 'sector_id']);
        });

        Schema::create('startup_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('startup_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('products')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['startup_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_translations');
        Schema::dropIfExists('startups');
    }
};
