<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Open data (ADR-0025): datasets with their languages, the files or links they are published as (distributions,
 * with the facts and preview worked out from the file), and download counts per day with no personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datasets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('pillar_id')->nullable()->constrained()->nullOnDelete();
            $table->string('licence', 24)->default('cc-by-4.0');
            $table->string('frequency', 16)->default('irregular');
            $table->json('keywords')->nullable();
            $table->date('temporal_from')->nullable();
            $table->date('temporal_to')->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->date('data_updated_on')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'pillar_id']);
            $table->index(['site_id', 'office_id']);
        });

        Schema::create('dataset_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('dataset_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('methodology')->nullable();
            $table->string('coverage', 200)->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['dataset_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('dataset_distributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('dataset_id')->constrained()->cascadeOnDelete();
            $table->string('format', 8);
            $table->json('titles')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('url', 500)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->json('columns')->nullable();
            $table->json('preview')->nullable();
            $table->json('personal_columns')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['dataset_id', 'sort']);
        });

        Schema::create('dataset_downloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dataset_distribution_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('count')->default(0);

            $table->unique(['dataset_distribution_id', 'day']);
        });

        Schema::table('project_kpis', function (Blueprint $table): void {
            $table->foreign('dataset_id')->references('id')->on('datasets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_kpis', function (Blueprint $table): void {
            $table->dropForeign(['dataset_id']);
        });
        Schema::dropIfExists('dataset_downloads');
        Schema::dropIfExists('dataset_distributions');
        Schema::dropIfExists('dataset_translations');
        Schema::dropIfExists('datasets');
    }
};
