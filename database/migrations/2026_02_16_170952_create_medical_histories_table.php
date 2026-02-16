<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->boolean('is_smoker')->nullable();
            $table->boolean('previous_surgeries')->nullable();
            $table->json('chronic_diseases')->nullable();
            $table->text('previous_surgeries_name')->nullable();
            $table->text('medications')->nullable();
            $table->text('allergies')->nullable();
            $table->text('family_history')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
