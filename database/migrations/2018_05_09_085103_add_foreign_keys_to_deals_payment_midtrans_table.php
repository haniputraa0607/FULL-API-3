<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDealsPaymentMidtransTable extends Migration {

	public function up()
	{
		Schema::table('deals_payment_midtrans', function(Blueprint $table)
		{
			$table->foreign('id_deals', 'fk_deals_payments_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('deals_payment_midtrans', function(Blueprint $table)
		{
			$table->dropForeign('fk_deals_payments_deals');
		});
	}

}
