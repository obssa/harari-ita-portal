<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A statistic may name the dataset it comes from (ADR-0027), and links to it from the numbers page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistics', function (Blueprint $table): void {
            $table->foreignUlid('dataset_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('statistics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dataset_id');
        });
    }
};
