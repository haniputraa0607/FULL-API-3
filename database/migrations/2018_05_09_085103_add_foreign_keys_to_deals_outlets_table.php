<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDealsOutletsTable extends Migration {

	public function up()
	{
		Schema::table('deals_outlets', function(Blueprint $table)
		{
			$table->foreign('id_deals', 'fk_deals_outlets_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_outlet', 'fk_deals_outlets_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('deals_outlets', function(Blueprint $table)
		{
			$table->dropForeign('fk_deals_outlets_deals');
			$table->dropForeign('fk_deals_outlets_outlets');
		});
	}

}
