<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDealsVouchersTable extends Migration {

	public function up()
	{
		Schema::create('deals_vouchers', function(Blueprint $table)
		{
			$table->increments('id_deals_voucher');
			$table->integer('id_deals')->unsigned()->index('fk_deals_vouchers_deals');
			$table->string('voucher_code', 20);
			$table->enum('deals_voucher_status', array('Available','Sent'))->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('deals_vouchers');
	}

}
