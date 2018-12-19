<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOutletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function(Blueprint $table)
		{
			$table->integer('id_outlet')->unsigned()->nullable()->after('id_transaction');
			$table->foreign('id_outlet', 'fk_transactions_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function(Blueprint $table) {
			$table->dropForeign('fk_transactions_outlets');
            $table->dropColumn('id_outlet');
        });
    }
}
