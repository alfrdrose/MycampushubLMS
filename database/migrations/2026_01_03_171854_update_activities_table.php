<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {

            // remove old enum status and add boolean visible
            $table->dropColumn('status');
            $table->boolean('visible')->default(0);

            // remove type enum
            $table->dropColumn('type');

            // add new toggles
            $table->boolean('accept_files')->default(0);
            $table->boolean('accept_text')->default(0);

            // add FK to subject_grading
            $table->foreignId('classwork_type')
                ->constrained('subject_grading')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {

            // rollback visible → status enum
            $table->dropColumn('visible');
            $table->enum('status', ['unpublished', 'published', 'closed'])
                  ->default('unpublished');

            // rollback new fields
            $table->dropColumn(['accept_files', 'accept_text']);

            // rollback type enum
            $table->enum('type', ['submissions', 'essay'])
                  ->default('submissions');

            // drop fk and column
            $table->dropForeign(['classwork_type']);
            $table->dropColumn('classwork_type');
        });
    }
};
