<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdDealsSubscriptionInDealsVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_vouchers', function (Blueprint $table) {
            $table->integer('id_deals_subscription')->unsigned()->nullable()->after('id_deals');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_vouchers', function (Blueprint $table) {
            $table->dropColumn('id_deals_subscription');
        });
    }
}
