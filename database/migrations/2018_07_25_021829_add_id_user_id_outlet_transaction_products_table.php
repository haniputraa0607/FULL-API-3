<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdUserIdOutletTransactionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_products', function(Blueprint $table)
		{
			$table->integer('id_outlet')->unsigned()->nullable()->after('id_product');
			$table->integer('id_user')->unsigned()->nullable()->after('id_outlet');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_products', function(Blueprint $table) {
            $table->dropColumn('id_outlet');
            $table->dropColumn('id_user');
        });
    }
}
