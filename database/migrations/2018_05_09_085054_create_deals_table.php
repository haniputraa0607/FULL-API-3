<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDealsTable extends Migration {

	public function up()
	{
		Schema::create('deals', function(Blueprint $table)
		{
			$table->increments('id_deals');
			$table->enum('deals_type', array('Deals','Hidden'))->default('Deals');
			$table->enum('deals_voucher_type', array('Auto generated','List Vouchers'))->default('Auto generated');
			$table->char('deals_promo_id', 200);
			$table->string('deals_title', 45);
			$table->string('deals_second_title')->nullable();
			$table->text('deals_description', 65535)->nullable();
			$table->string('deals_short_description')->nullable();
			$table->string('deals_image', 200)->nullable();
			$table->string('deals_video', 100)->nullable();
			$table->integer('id_product')->unsigned()->nullable()->index('fk_Deals_Product1_idx');
			$table->dateTime('deals_start');
			$table->dateTime('deals_end');
			$table->dateTime('deals_publish_start');
			$table->dateTime('deals_publish_end');
			$table->integer('deals_voucher_duration')->nullable();
			$table->dateTime('deals_voucher_expired')->nullable();
			$table->integer('deals_voucher_price_point')->nullable();
			$table->integer('deals_voucher_price_cash')->nullable();
			$table->integer('deals_total_voucher')->default(0);
			$table->integer('deals_total_claimed')->default(0);
			$table->integer('deals_total_redeemed')->default(0);
			$table->integer('deals_total_used')->default(0);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('deals');
	}

}
