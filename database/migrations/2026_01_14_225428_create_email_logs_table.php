<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            // What attendance session this email was for
            $table->unsignedBigInteger('attendance_id');

            // Who received it
            $table->string('guardian_email');

            // When it was sent
            $table->timestamp('sent_at');

            $table->timestamps();

            // Prevent duplicates
            $table->unique(['attendance_id', 'guardian_email']);

            // Optional FK safety (comment out if you don’t want constraints)
            $table->foreign('attendance_id')
                ->references('id')
                ->on('attendances')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
