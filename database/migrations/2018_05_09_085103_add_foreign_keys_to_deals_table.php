<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDealsTable extends Migration {

	public function up()
	{
		Schema::table('deals', function(Blueprint $table)
		{
			$table->foreign('id_product', 'fk_deals_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}

	public function down()
	{
		Schema::table('deals', function(Blueprint $table)
		{
			$table->dropForeign('fk_deals_products');
		});
	}

}
