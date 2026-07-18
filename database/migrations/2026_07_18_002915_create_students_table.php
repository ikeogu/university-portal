<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('mat_no')->unique();
            $table->unsignedSmallInteger('entry_year')->index();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('state_of_origin')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('mode_of_study');
            $table->string('photo_path')->nullable();
            $table->string('access_pin_hash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
