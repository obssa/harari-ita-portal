<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The service status page (PLAN.md §4, §5; ADR-0018): the systems the Agency runs, incidents and planned
 * maintenance on them, and the dated updates of each incident. Names and words are short and stored per language.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->json('names');
            $table->foreignUlid('service_item_id')->nullable()->constrained('service_items')->nullOnDelete();
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['site_id', 'visible', 'sort']);
        });

        Schema::create('incidents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('kind', 16)->default('incident');
            $table->json('titles');
            $table->string('impact', 24);
            $table->string('stage', 16);
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'resolved_at', 'started_at']);
        });

        Schema::create('incident_status_component', function (Blueprint $table): void {
            $table->foreignUlid('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('status_component_id')->constrained()->cascadeOnDelete();

            $table->primary(['incident_id', 'status_component_id']);
        });

        Schema::create('incident_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('incident_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 16);
            $table->json('bodies');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incident_status_component');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('status_components');
    }
};
