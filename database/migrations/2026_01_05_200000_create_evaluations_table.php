<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluations')) {
            return;
        }

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id(); // 1. id

            $table->string('title'); // 2. title
            $table->text('description')->nullable(); // 3. description

            $table->timestamp('time_posted')->nullable(); // 4. time_posted

            $table->foreignId('section_subject_id') // 5. FK
                  ->constrained('section_subject')
                  ->cascadeOnDelete();

            $table->boolean('visible')->default(true); // 6. visible

            $table->enum('grading_period', ['1st', '2nd', '3rd', '4th']); // 8. grading_period

            $table->timestamps(); // 9 & 10
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
