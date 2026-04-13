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
        Schema::create('key_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_analysis_result_id')->constrained()->cascadeOnDelete();
            $table->enum('priority', ['low', 'medium', 'high']);
            $table->string('title')->nullable();
            $table->text('insight');
            $table->boolean('is_ai_generated')->default(false);
            $table->json('evidence')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_points');
    }
};
