<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notes and history lines on a person the Agency deals with (volunteers and
     * mentors today); the text is encrypted.
     */
    public function up(): void
    {
        Schema::create('people_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('notable_type');
            $table->string('notable_id', 36);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 16);
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['notable_type', 'notable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people_notes');
    }
};
