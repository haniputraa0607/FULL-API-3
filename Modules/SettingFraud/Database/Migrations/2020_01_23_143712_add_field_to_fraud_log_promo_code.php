<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldToFraudLogPromoCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fraud_detection_log_check_promo_code', function (Blueprint $table) {
            $table->integer('fraud_parameter_detail_time')->nullable()->after('fraud_setting_parameter_detail');
            $table->integer('fraud_hold_time')->nullable()->after('fraud_setting_parameter_detail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fraud_detection_log_check_promo_code', function (Blueprint $table) {
            $table->dropColumn('fraud_parameter_detail_time');
            $table->dropColumn('fraud_hold_time');
        });
    }
}
