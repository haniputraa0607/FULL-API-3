<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDealsVouchersTable extends Migration {

	public function up()
	{
		Schema::table('deals_vouchers', function(Blueprint $table)
		{
			$table->foreign('id_deals', 'fk_deals_vouchers_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('deals_vouchers', function(Blueprint $table)
		{
			$table->dropForeign('fk_deals_vouchers_deals');
		});
	}

}
