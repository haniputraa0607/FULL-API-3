<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNullableDeviceTypeToPromoCampaignReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE promo_campaign_reports MODIFY COLUMN device_type ENUM('Android', 'IOS') NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE promo_campaign_reports MODIFY COLUMN device_type ENUM('Android', 'IOS')");
    }
}
