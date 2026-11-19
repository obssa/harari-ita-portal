<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0053: a call, vacancy or internship can take applications through a form built in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->foreignUlid('apply_form_id')->nullable()->after('apply_email')->constrained('forms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('apply_form_id');
        });
    }
};
