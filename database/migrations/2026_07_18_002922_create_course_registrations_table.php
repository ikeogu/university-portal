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

            $table->unique(['student_id', 'course_id', 'academic_session_id'], 'course_reg_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};