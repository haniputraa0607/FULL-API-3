<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToDealsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_users', function (Blueprint $table) {
        	$table->index('redeemed_at');
        	$table->index('paid_status');
        	$table->index('used_at');
        	$table->index('voucher_expired_at');
        	$table->index('claimed_at');
        	$table->index('voucher_price_cash');
        	$table->index('voucher_hash_code');
        	$table->index('created_at');
        	$table->index('is_used');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_users', function (Blueprint $table) {
        	$table->dropIndex(['redeemed_at']);
        	$table->dropIndex(['paid_status']);
        	$table->dropIndex(['used_at']);
        	$table->dropIndex(['voucher_expired_at']);
        	$table->dropIndex(['claimed_at']);
        	$table->dropIndex(['voucher_price_cash']);
        	$table->dropIndex(['voucher_hash_code']);
        	$table->dropIndex(['created_at']);
        	$table->dropIndex(['is_used']);
        });
    }
}
