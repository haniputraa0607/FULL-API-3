<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateIndexingToDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->dropIndex(['deals_promo_id']);
        	$table->dropIndex(['deals_second_title']);
        	$table->dropIndex(['deals_promo_id_type']);
        	$table->dropIndex(['deals_total_voucher']);
        	$table->dropIndex(['deals_start']);
        	$table->dropIndex(['deals_voucher_start']);
        	$table->dropIndex(['deals_voucher_expired']);
        	$table->dropIndex(['deals_voucher_duration']);
        	$table->dropIndex(['user_limit']);
        	$table->dropIndex(['deals_total_claimed']);
        	$table->dropIndex(['deals_total_redeemed']);
        	$table->dropIndex(['deals_total_used']);
        	$table->dropIndex(['created_at']);
        	$table->dropIndex(['updated_at']);
        });

        Schema::table('deals_users', function (Blueprint $table) {
        	$table->dropIndex(['voucher_price_cash']);
        	$table->dropIndex(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->index('deals_promo_id');
        	$table->index('deals_second_title');

        	$table->index('deals_promo_id_type');
        	$table->index('deals_total_voucher');
        	$table->index('deals_start');
        	$table->index('deals_voucher_start');
        	$table->index('deals_voucher_expired');
        	$table->index('deals_voucher_duration');
        	$table->index('user_limit');
        	$table->index('deals_total_claimed');
        	$table->index('deals_total_redeemed');
        	$table->index('deals_total_used');
        	$table->index('created_at');
        	$table->index('updated_at');
        });

        Schema::table('deals_users', function (Blueprint $table) {
        	$table->index('voucher_price_cash');
        	$table->index('created_at');
        });
    }
}
