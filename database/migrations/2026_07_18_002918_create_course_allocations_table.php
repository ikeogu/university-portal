<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'user_id', 'academic_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_allocations');
    }
};
