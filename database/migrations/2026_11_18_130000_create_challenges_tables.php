<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Challenges and hackathons (PLAN.md §4, §5; ADR-0022): the brief per language, the phases, the judging rubric,
 * the teams that register and submit, the judges assigned to each team with their conflict declarations, and the
 * scores and published results.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('registration_opens_at');
            $table->timestamp('registration_closes_at');
            $table->timestamp('submission_closes_at');
            $table->date('results_on')->nullable();
            $table->timestamp('results_published_at')->nullable();
            $table->unsignedTinyInteger('max_team_size')->default(5);
            $table->json('criteria')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'registration_opens_at']);
        });

        Schema::create('challenge_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('challenge_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('prizes')->nullable();
            $table->text('rules')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['challenge_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('challenge_teams', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('challenge_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 16)->unique();
            $table->string('locale', 8);
            $table->string('name', 120);
            $table->text('contact_name')->nullable();
            $table->text('contact_email')->nullable();
            $table->text('contact_phone')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->char('phone_hash', 64)->nullable()->index();
            $table->text('members')->nullable();
            $table->string('submission_title', 200)->nullable();
            $table->text('submission_description')->nullable();
            $table->json('submission_links')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedSmallInteger('rank')->nullable();
            $table->string('award', 120)->nullable();
            $table->boolean('show_in_results')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('erased_at')->nullable();
            $table->timestamps();

            $table->index(['challenge_id', 'submitted_at']);
            $table->unique(['challenge_id', 'name']);
        });

        Schema::create('judge_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('challenge_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('scores')->nullable();
            $table->decimal('total', 5, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->timestamp('conflict_declared_at')->nullable();
            $table->string('conflict_note', 500)->nullable();
            $table->timestamps();

            $table->unique(['challenge_team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_assignments');
        Schema::dropIfExists('challenge_teams');
        Schema::dropIfExists('challenge_translations');
        Schema::dropIfExists('challenges');
    }
};
