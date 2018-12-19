<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAdd1ToLogTopupManualTable extends Migration
{
    public function up()
    {
        Schema::table('log_topup_manuals', function (Blueprint $table) {
            $table->unsignedInteger('id_log_topup')->after('id_transaction');
        });
    }

    public function down()
    {
        Schema::table('log_topup_manuals', function (Blueprint $table) {
            $table->dropColumn('id_log_topup');
        });
    }
}
