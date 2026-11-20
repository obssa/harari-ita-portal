<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * One valid certificate per enrolment, kept by the database as well as by IssueCertificate: `valid` is 1 while the
 * certificate is not revoked and null afterwards, and a unique index on (enrolment, valid) allows many nulls.
 * Revoked certificates stay, so a learner can be issued a new one after a revocation.
 *
 * The generated column reads only `revoked_at`: MariaDB (staging) refuses a stored generated column that returns a
 * CHAR column such as the enrolment ULID, because PAD_CHAR_TO_FULL_LENGTH could change its value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->unsignedTinyInteger('valid')->nullable()->storedAs('if(`revoked_at` is null, 1, null)')->after('revoke_reason');
            $table->unique(['enrolment_id', 'valid']);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropUnique(['enrolment_id', 'valid']);
            $table->dropColumn('valid');
        });
    }
};
