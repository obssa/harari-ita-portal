<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The services catalogue (PLAN.md §4, §5; ADR-0016): what the Agency offers, who can use it, what to bring, the
 * steps, fees and typical time, the channels and the responsible office, in each language.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUlid('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->json('audiences')->nullable();
            $table->json('channels')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('rank')->default(0);
            $table->date('reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'featured', 'rank']);
            $table->index(['site_id', 'category_id']);
            $table->index(['site_id', 'office_id']);
        });

        Schema::create('service_item_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('service_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 200);
            $table->string('slug', 160);
            $table->text('summary')->nullable();
            $table->json('body')->nullable();
            $table->text('eligibility')->nullable();
            $table->text('documents')->nullable();
            $table->text('steps')->nullable();
            $table->string('fees', 300)->nullable();
            $table->string('duration', 200)->nullable();
            $table->json('faq')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->mediumText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['service_item_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_item_translations');
        Schema::dropIfExists('service_items');
    }
};
