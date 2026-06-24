<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_replies', function (Blueprint $table) {
            $table->id();

            // FK to announcements_models
            $table->unsignedBigInteger('announcement_id');
            $table->foreign('announcement_id')
                  ->references('id')
                  ->on('announcements_models')
                  ->onDelete('cascade');

            // User who replied (student or teacher)
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->longText('reply')->charset('utf8mb4');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_replies');
    }
};
    