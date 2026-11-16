<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programmes (incubation, acceleration, training) and their cohorts (PLAN.md §4, §5; ADR-0021): what each programme
 * offers and to whom, in each language, the questions its applicants answer, and the cohorts with their application
 * windows and places.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programmes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('kind', 16);
            $table->string('partner', 200)->nullable();
            $table->foreignId('cover_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('questions')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'sort']);
        });

        Schema::create('programme_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('programme_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('eligibility')->nullable();
            $table->text('benefits')->nullable();
            $table->text('timeline')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['programme_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('cohorts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('programme_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamp('opens_at');
            $table->timestamp('closes_at');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('places')->nullable();
            $table->timestamps();

            $table->index(['programme_id', 'opens_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
        Schema::dropIfExists('programme_translations');
        Schema::dropIfExists('programmes');
    }
};
