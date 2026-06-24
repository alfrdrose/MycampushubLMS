<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_grading', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key
            $table->unsignedBigInteger('subject_id');
            $table->foreign('subject_id')
                  ->references('id')
                  ->on('subject_models')   // <- change to your actual table name if different
                  ->onDelete('cascade');

            $table->string('classwork_type');
            $table->decimal('percentage', 5, 2); // e.g., 20.50
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_grading');
    }
};
