<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDealsPaymentManualsTable extends Migration {

	public function up()
	{
		Schema::table('deals_payment_manuals', function(Blueprint $table)
		{
			$table->foreign('id_deals', 'fk_deals_payment_manuals_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_manual_payment_method', 'fk_deals_payments_manual_payments')->references('id_manual_payment_method')->on('manual_payment_methods')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_user_confirming', 'fk_deals_payments_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('deals_payment_manuals', function(Blueprint $table)
		{
			$table->dropForeign('fk_deals_payment_manuals_deals');
			$table->dropForeign('fk_deals_payments_manual_payments');
			$table->dropForeign('fk_deals_payments_users');
		});
	}

}
