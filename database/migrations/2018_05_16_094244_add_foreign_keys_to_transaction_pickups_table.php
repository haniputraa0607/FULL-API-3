<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToTransactionPickupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_pickups', function(Blueprint $table)
		{
			$table->foreign('id_transaction', 'fk_transaction_pickups_transaction')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pickups', function(Blueprint $table)
		{
			$table->dropForeign('fk_transaction_pickups_transaction');
		});
    }
}
