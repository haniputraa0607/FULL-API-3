<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionsToQuestOutletLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quest_outlet_logs', function (Blueprint $table) {
            $table->string('id_transactions')->after('id_outlet')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quest_outlet_logs', function (Blueprint $table) {
            $table->dropColumn('id_transactions');
        });
    }
}
