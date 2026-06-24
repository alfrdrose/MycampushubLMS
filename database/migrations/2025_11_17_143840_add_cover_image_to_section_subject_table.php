<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('section_subject', function (Blueprint $table) {
            $table->string('coverImage')->nullable()->after('end_time');
        });
    }

    public function down()
    {
        Schema::table('section_subject', function (Blueprint $table) {
            $table->dropColumn('coverImage');
        });
    }

};
