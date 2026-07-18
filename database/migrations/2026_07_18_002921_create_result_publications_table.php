<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_publications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('academic_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->unsignedTinyInteger('semester');
            $table->timestamp('published_at')->nullable();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_session_id', 'level', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publications');
    }
};
