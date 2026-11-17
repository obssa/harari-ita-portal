<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects, extended (ADR-0024): the pillar, a RAG status with a staff-only reason, spend next to the budget,
 * milestones, KPIs with dated readings, and staff-only risks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignUlid('pillar_id')->nullable()->after('office_id')->constrained()->nullOnDelete();
            $table->string('rag_status', 8)->nullable()->after('status');
            $table->string('rag_reason', 300)->nullable()->after('rag_status');
            $table->timestamp('rag_updated_at')->nullable()->after('rag_reason');
            $table->decimal('spent', 16, 2)->nullable()->after('budget');

            $table->index(['site_id', 'pillar_id']);
        });

        Schema::create('project_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->json('titles');
            $table->date('due_on')->nullable();
            $table->date('done_on')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort']);
        });

        Schema::create('project_kpis', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->json('names');
            $table->json('units')->nullable();
            $table->decimal('baseline', 20, 4)->nullable();
            $table->decimal('target', 20, 4);
            $table->boolean('higher_is_better')->default(true);
            // The dataset the readings come from; the foreign key is added with the datasets table (phase 4.3).
            $table->ulid('dataset_id')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'sort']);
        });

        Schema::create('project_kpi_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_kpi_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 20, 4);
            $table->date('as_of');
            $table->string('note', 300)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_kpi_id', 'as_of']);
        });

        Schema::create('project_risks', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('likelihood', 8);
            $table->string('impact', 8);
            $table->string('status', 12)->default('open');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('mitigation')->nullable();
            $table->date('review_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_risks');
        Schema::dropIfExists('project_kpi_readings');
        Schema::dropIfExists('project_kpis');
        Schema::dropIfExists('project_milestones');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex(['site_id', 'pillar_id']);
            $table->dropConstrainedForeignId('pillar_id');
            $table->dropColumn(['rag_status', 'rag_reason', 'rag_updated_at', 'spent']);
        });
    }
};
