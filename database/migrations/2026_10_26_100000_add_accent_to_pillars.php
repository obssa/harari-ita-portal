<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ADR-0065: each pillar or wing has its colour from the brand (the
     * women's wing magenta, the youth wing blue), chosen in the admin.
     */
    public function up(): void
    {
        Schema::table('pillars', function (Blueprint $table) {
            $table->string('accent', 16)->nullable()->after('socials');
        });
    }

    public function down(): void
    {
        Schema::table('pillars', fn (Blueprint $table) => $table->dropColumn('accent'));
    }
};
