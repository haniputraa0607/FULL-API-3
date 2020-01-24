<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResultTextToFraudSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fraud_settings', function (Blueprint $table) {
            $table->string('result_text', 255)->nullable()->after('parameter_detail');
            $table->integer('hold_time')->nullable()->after('parameter_detail');
            $table->integer('parameter_detail_time')->nullable()->after('parameter_detail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fraud_settings', function (Blueprint $table) {
            $table->dropColumn('parameter_detail_time');
            $table->dropColumn('result_text');
            $table->dropColumn('hold_time');
        });
    }
}
