<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('credit_units_at_entry');
            $table->unsignedTinyInteger('ca')->nullable();
            $table->unsignedTinyInteger('exam')->nullable();
            $table->foreignUlid('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'academic_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
