<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_audit_log', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('score_id')->constrained('scores')->cascadeOnDelete();
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('old_ca')->nullable();
            $table->unsignedTinyInteger('old_exam')->nullable();
            $table->unsignedTinyInteger('new_ca')->nullable();
            $table->unsignedTinyInteger('new_exam')->nullable();
            $table->string('source')->default('manual');
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_audit_log');
    }
};
