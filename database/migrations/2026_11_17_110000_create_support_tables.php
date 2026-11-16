<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IT support tickets (PLAN.md §4, §5; ADR-0017): requests from public offices and residents, the conversation on
 * each, and the response and resolution targets by category and priority. The requester's details and the words
 * of the request are encrypted; keyed hashes of the email and phone let a privacy request find them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('category', 24)->nullable();
            $table->string('priority', 16);
            $table->unsignedSmallInteger('response_hours');
            $table->unsignedSmallInteger('resolve_hours');
            $table->timestamps();

            $table->unique(['site_id', 'category', 'priority']);
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('reference', 16)->unique();
            $table->string('locale', 8);
            $table->string('requester_type', 16);
            $table->string('requester_office', 200)->nullable();
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->char('phone_hash', 64)->nullable()->index();
            $table->foreignUlid('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignUlid('service_item_id')->nullable()->constrained('service_items')->nullOnDelete();
            $table->string('category', 24);
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('new');
            $table->string('channel', 16)->default('web');
            $table->text('subject');
            $table->text('description');
            $table->json('files')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('resolve_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable();
            $table->text('satisfaction_note')->nullable();
            $table->timestamp('erased_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['site_id', 'office_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });

        Schema::create('ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('from_requester')->default(false);
            $table->boolean('internal')->default(false);
            $table->text('body');
            $table->json('files')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('sla_policies');
    }
};
