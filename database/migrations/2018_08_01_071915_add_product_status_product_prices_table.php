<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductStatusProductPricesTable extends Migration
{

    public function up()
    {
        Schema::table('product_prices', function(Blueprint $table)
		{
			$table->enum('product_status', array('Active','Inactive'))->default('Active')->after('product_visibility');
		});
    }

    public function down()
    {
        Schema::table('product_prices', function(Blueprint $table) {
            $table->dropColumn('product_status');
        });
    }
}
