<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applications to a programme cohort (PLAN.md §5; ADR-0021): the applicant's details and answers (encrypted), the
 * stage it has reached, the rubric scores and the decision note the applicant reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('reference', 16)->unique();
            $table->foreignUlid('programme_id')->constrained()->restrictOnDelete();
            $table->foreignId('cohort_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('startup_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 8);
            $table->text('applicant_name')->nullable();
            $table->text('applicant_email')->nullable();
            $table->text('applicant_phone')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->char('phone_hash', 64)->nullable()->index();
            $table->string('team_name', 200)->nullable();
            $table->text('answers')->nullable();
            $table->string('stage', 16)->default('received');
            $table->json('scores')->nullable();
            $table->decimal('score', 4, 2)->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('erased_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'programme_id', 'stage']);
            $table->index(['cohort_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
