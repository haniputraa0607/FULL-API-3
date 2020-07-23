<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSendExpiredDealsToPromotionContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
        	$table->boolean('send_deals_expired')->nullable()->after('promotion_sum_transaction')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
        	$table->dropColumn('send_deals_expired');
        });
    }
}
