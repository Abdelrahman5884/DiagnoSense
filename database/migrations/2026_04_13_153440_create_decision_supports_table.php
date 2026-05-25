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
        Schema::create('decision_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_analysis_result_id')->constrained()->cascadeOnDelete();
            $table->string('condition');
            $table->string('probability');
            $table->string('status');
            $table->text('clinical_reasoning');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_supports');
    }
};
