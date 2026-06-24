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
    Schema::table('generated_reviewers', function (Blueprint $table) {
        $table->timestamp('completed_at')->nullable()->after('data');
    });
}

public function down()
{
    Schema::table('generated_reviewers', function (Blueprint $table) {
        $table->dropColumn('completed_at');
    });
}
};
