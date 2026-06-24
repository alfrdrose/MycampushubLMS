<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_essays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');

            $table->longText('essay_text');
            $table->integer('word_count')->nullable();

            $table->timestamps();

            $table->foreign('submission_id')
                  ->references('id')
                  ->on('submission_models')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_essays');
    }
};
