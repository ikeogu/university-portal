<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('title');
            $table->unsignedTinyInteger('credit_units');
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('level');
            $table->string('category')->default('core');
            $table->string('elective_group')->nullable();
            $table->unsignedTinyInteger('choose_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
