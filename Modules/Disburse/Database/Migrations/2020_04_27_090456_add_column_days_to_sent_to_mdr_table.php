<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnDaysToSentToMdrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mdr', function (Blueprint $table) {
            $table->string('days_to_sent', 255)->nullable()->after('charged');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mdr', function (Blueprint $table) {
            $table->dropColumn('days_to_sent');
        });
    }
}
