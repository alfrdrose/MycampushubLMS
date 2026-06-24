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
        Schema::create('activities', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();

        // Stores date + time (YYYY-MM-DD HH:MM:SS)
        $table->timestamp('time_posted')->nullable();
        $table->timestamp('due_date')->nullable();

        $table->foreignId('section_subject_id')
            ->constrained('section_subject')
            ->onDelete('cascade');

        $table->enum('status', ['unpublished', 'published', 'closed'])->default('unpublished');
        $table->enum('type', ['submissions', 'essay'])->default('submissions');
        $table->boolean('accepting')->default(true);
        $table->unsignedInteger('max_number_files')->default(1);

        // Like "1st Quarter", "2nd Quarter", etc.
        $table->string('grading_period')->nullable();

        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
