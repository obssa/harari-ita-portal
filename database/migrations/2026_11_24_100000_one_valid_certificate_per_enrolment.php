<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * One valid certificate per enrolment, kept by the database as well as by IssueCertificate: `valid_for` holds the
 * enrolment while the certificate is not revoked and is null afterwards, and a unique index allows many nulls.
 * Revoked certificates stay, so a learner can be issued a new one after a revocation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->char('valid_for', 26)->nullable()->storedAs('if(`revoked_at` is null, `enrolment_id`, null)')->after('revoke_reason');
            $table->unique('valid_for');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropUnique(['valid_for']);
            $table->dropColumn('valid_for');
        });
    }
};
