<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviewers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('learning_objective');
            $table->text('mini_lesson');
            $table->json('mini_quiz')->nullable();
            $table->string('source_classwork_type')->nullable();
            $table->unsignedBigInteger('source_classwork_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviewers');
    }
};
