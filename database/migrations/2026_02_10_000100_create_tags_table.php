<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id(); // unsigned big integer, primary key

            $table->string('name'); // Human-readable name (Math, Algebra)
            $table->string('slug')->unique(); // URL-safe unique identifier

            $table->timestamps(); // created_at, updated_at

            $table->index('name'); // Optional: faster lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
