<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreFieldToFraudSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fraud_settings', function (Blueprint $table) {
            $table->tinyInteger('auto_suspend_status')->default(0)->after('parameter_detail');
            $table->string('auto_suspend_value',100)->nullable()->after('parameter_detail');
            $table->integer('auto_suspend_time_period')->nullable()->after('parameter_detail');
            $table->tinyInteger('forward_admin_status')->default(0)->after('parameter_detail');
            $table->enum('fraud_settings_status', ['Active', 'Inactive'])->default('Active')->after('whatsapp_content');
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
            $table->dropColumn('auto_suspend_status');
            $table->dropColumn('auto_suspend_value');
            $table->dropColumn('auto_suspend_time_period');
            $table->dropColumn('forward_admin_status');
            $table->dropColumn('fraud_settings_status');
        });
    }
}
