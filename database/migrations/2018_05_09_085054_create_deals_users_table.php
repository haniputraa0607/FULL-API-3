<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDealsUsersTable extends Migration {

	public function up()
	{
		Schema::create('deals_users', function(Blueprint $table)
		{
			$table->increments('id_deals_user');
			$table->integer('id_deals_voucher')->unsigned()->index('fk_deals_users_deals_vouchers');
			$table->integer('id_user')->unsigned()->index('fk_deals_users_users');
			$table->integer('id_outlet')->unsigned()->nullable();
			$table->string('voucher_hash', 100)->nullable();
			$table->dateTime('claimed_at')->nullable();
			$table->dateTime('redeemed_at')->nullable();
			$table->dateTime('used_at')->nullable();
			$table->integer('voucher_price_point')->nullable();
			$table->integer('voucher_price_cash')->nullable();
			$table->enum('paid_status', array('Free','Pending','Paid','Completed','Cancelled'))->default('Pending');
			$table->dateTime('voucher_expired_at')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('deals_users');
	}

}
