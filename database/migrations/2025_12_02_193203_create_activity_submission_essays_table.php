<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_submission_essays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('activity_submissions')->onDelete('cascade');
            $table->text('essay_text');
            $table->integer('word_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_submission_essays');
    }
};
