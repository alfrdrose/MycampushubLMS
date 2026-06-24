<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id');

            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type');

            $table->timestamps(); // when the tag was attached

            // Prevent duplicate tag assignments
            $table->unique(
                ['tag_id', 'taggable_id', 'taggable_type'],
                'taggables_unique'
            );

            // Indexes for performance
            $table->index('tag_id');
            $table->index(['taggable_type', 'taggable_id']);

            // Foreign key constraint
            $table->foreign('tag_id')
                ->references('id')
                ->on('tags')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
