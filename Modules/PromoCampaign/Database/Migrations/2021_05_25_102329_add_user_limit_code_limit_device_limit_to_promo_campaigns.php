<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserLimitCodeLimitDeviceLimitToPromoCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->integer('user_limit')->default(0)->after('promo_description');
        	$table->integer('code_limit')->default(0)->after('user_limit');
        	$table->integer('device_limit')->default(0)->after('code_limit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->dropColumn('user_limit');
        	$table->dropColumn('code_limit');
        	$table->dropColumn('device_limit');
        });
    }
}
