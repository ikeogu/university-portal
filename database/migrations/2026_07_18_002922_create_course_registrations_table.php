<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Explicit short name — the auto-generated one
            // ("course_registrations_student_id_course_id_academic_session_id_unique",
            // 68 chars) exceeds MySQL's 64-character identifier limit. Passes on
            // SQLite (used locally), which doesn't enforce that limit, so this
            // only surfaces against real MySQL.
            $table->unique(['student_id', 'course_id', 'academic_session_id'], 'course_registrations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};
