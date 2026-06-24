<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements_models', function (Blueprint $table) {
            $table->id();

            // The subject where this announcement belongs
            $table->unsignedBigInteger('section_subject_id');
            $table->foreign('section_subject_id')
                  ->references('id')
                  ->on('section_subject') // change if your table name differs
                  ->onDelete('cascade');

            // The teacher who posted the announcement
            $table->unsignedBigInteger('teacher_id');
            $table->foreign('teacher_id')
                  ->references('id')
                  ->on('teacher_models') // IMPORTANT: your teacher table name!
                  ->onDelete('cascade');

            $table->string('title');
            $table->longText('body')->charset('utf8mb4');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements_models');
    }
};
