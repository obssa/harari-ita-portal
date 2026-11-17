<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The digital skills academy (PLAN.md §4, §5; ADR-0028..0030): courses with their languages, instructors, cohorts
 * (one run of a course) with their sessions and instructors, the learners' enrolments (personal details encrypted),
 * attendance per session, and certificates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('pillar_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 16)->default('beginner');
            $table->string('mode', 16)->default('in_person');
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->json('languages')->nullable();
            $table->string('partner', 200)->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedTinyInteger('attendance_required')->default(80);
            $table->string('signatory_name', 120)->nullable();
            $table->json('signatory_titles')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'sort']);
        });

        Schema::create('course_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('audience')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('outcomes')->nullable();
            $table->text('syllabus')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('instructors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->json('titles')->nullable();
            $table->boolean('public')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'name']);
        });

        Schema::create('course_cohorts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('mode', 16);
            $table->foreignUlid('office_id')->nullable()->constrained()->nullOnDelete();
            $table->json('venue_notes')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamp('enrol_opens_at')->nullable();
            $table->timestamp('enrol_closes_at')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('waitlist')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'starts_on']);
            $table->index('starts_on');
        });

        Schema::create('course_cohort_instructor', function (Blueprint $table): void {
            $table->foreignId('course_cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();

            $table->primary(['course_cohort_id', 'instructor_id']);
        });

        Schema::create('course_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_cohort_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->json('titles')->nullable();
            $table->timestamps();

            $table->index(['course_cohort_id', 'starts_at']);
        });

        Schema::create('enrolments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('course_cohort_id')->constrained()->restrictOnDelete();
            $table->string('locale', 8);
            $table->text('name')->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->string('phone_hash', 64)->nullable()->index();
            $table->string('email_hash', 64)->nullable()->index();
            $table->string('status', 16);
            $table->text('token')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('promoted_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('erased_at')->nullable();
            $table->timestamps();

            $table->index(['course_cohort_id', 'status', 'created_at']);
        });

        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('enrolment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_session_id', 'enrolment_id']);
            $table->index('enrolment_id');
        });

        Schema::create('certificates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('enrolment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_cohort_id')->constrained()->restrictOnDelete();
            $table->string('code', 13)->unique();
            $table->text('holder_name')->nullable();
            $table->string('holder_initials', 24)->nullable();
            $table->string('signature', 64);
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason', 300)->nullable();
            $table->timestamps();

            $table->index(['course_cohort_id', 'issued_at']);
            $table->index(['enrolment_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('enrolments');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('course_cohort_instructor');
        Schema::dropIfExists('course_cohorts');
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('course_translations');
        Schema::dropIfExists('courses');
    }
};
