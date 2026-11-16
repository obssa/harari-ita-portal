<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0019: a service can be "proposed": suggested for the Agency and shown with a notice until the Agency
 * confirms it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_items', function (Blueprint $table): void {
            $table->boolean('proposed')->default(false)->after('featured');
        });
    }

    public function down(): void
    {
        Schema::table('service_items', function (Blueprint $table): void {
            $table->dropColumn('proposed');
        });
    }
};
