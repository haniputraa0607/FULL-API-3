<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDealsOutletsTable extends Migration {

	public function up()
	{
		Schema::create('deals_outlets', function(Blueprint $table)
		{
			$table->integer('id_deals')->unsigned()->nullable();
			$table->integer('id_outlet')->unsigned()->nullable()->index('fk_deals_outlets_outlets');
			$table->index(['id_deals','id_outlet'], 'id_deals');
		});
	}

	public function down()
	{
		Schema::drop('deals_outlets');
	}

}
