<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opportunities (PLAN.md §4, §5; ADR-0032, ADR-0033): tenders, calls for proposals, vacancies and internships with
 * their languages, the library documents they attach, and the dated log of addenda, clarifications, extensions and
 * cancellations. Advisories are posts of their own type: a severity on the post, the affected systems and the steps
 * to take per language (ADR-0034).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('type', 16);
            $table->string('reference', 60)->nullable();
            $table->foreignUlid('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('pillar_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at');
            $table->timestamp('original_closes_at')->nullable();
            $table->timestamp('bid_opening_at')->nullable();
            $table->unsignedSmallInteger('positions')->nullable();
            $table->string('employment_type', 16)->nullable();
            $table->string('apply_url', 500)->nullable();
            $table->string('apply_email', 190)->nullable();
            $table->boolean('featured')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'closes_at']);
            $table->index(['archived_at', 'closes_at']);
        });

        Schema::create('opportunity_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('eligibility')->nullable();
            $table->text('how_to_apply')->nullable();
            $table->text('outcome')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['opportunity_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('document_opportunity', function (Blueprint $table): void {
            $table->foreignUlid('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);

            $table->primary(['opportunity_id', 'document_id']);
        });

        Schema::create('opportunity_addenda', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('opportunity_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('kind', 16);
            $table->json('titles');
            $table->json('bodies')->nullable();
            $table->timestamp('previous_closes_at')->nullable();
            $table->timestamp('new_closes_at')->nullable();
            $table->foreignUlid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('corrected_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['opportunity_id', 'number']);
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->string('severity', 16)->nullable()->after('type');
        });

        Schema::table('post_translations', function (Blueprint $table): void {
            $table->text('affected')->nullable()->after('excerpt');
            $table->text('actions')->nullable()->after('affected');
        });
    }

    public function down(): void
    {
        Schema::table('post_translations', function (Blueprint $table): void {
            $table->dropColumn(['affected', 'actions']);
        });
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('severity');
        });
        Schema::dropIfExists('opportunity_addenda');
        Schema::dropIfExists('document_opportunity');
        Schema::dropIfExists('opportunity_translations');
        Schema::dropIfExists('opportunities');
    }
};
