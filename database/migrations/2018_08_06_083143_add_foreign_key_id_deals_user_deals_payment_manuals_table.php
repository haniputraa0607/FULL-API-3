<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyIdDealsUserDealsPaymentManualsTable extends Migration
{
    public function up()
	{
		Schema::table('deals_payment_manuals', function(Blueprint $table)
		{
			$table->foreign('id_deals_user', 'fk_deals_payment_manuals_deals_users')->references('id_deals_user')->on('deals_users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('deals_payment_manuals', function(Blueprint $table)
		{
			$table->dropForeign('fk_deals_payment_manuals_deals_users');
		});
	}
}
