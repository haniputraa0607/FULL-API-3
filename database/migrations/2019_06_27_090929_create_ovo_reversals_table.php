<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOvoReversalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
	{
		Schema::create('ovo_reversals', function(Blueprint $table)
		{
			$table->increments('id_ovo_reversal');
			$table->integer('id_transaction')->unsigned()->index('fk_ovo_reversals_transactions');
			$table->integer('id_transaction_payment_ovo')->unsigned()->index('fk_ovo_reversals_transaction_payment_ovos');
			$table->datetime('date_push_to_pay');
			$table->text('request');
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ovo_reversals');
    }
}
