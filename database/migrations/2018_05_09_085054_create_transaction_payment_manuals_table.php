<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionPaymentManualsTable extends Migration {

	public function up()
	{
		Schema::create('transaction_payment_manuals', function(Blueprint $table)
		{
			$table->increments('id_transaction_payment_manual');
			$table->integer('id_transaction')->unsigned()->default(0)->index('fk_transaction_payment_manuals_transactions');
			$table->integer('id_manual_payment_method')->unsigned()->index('fk_transaction_payments_manual_payments');
			$table->date('payment_date');
			$table->time('payment_time');
			$table->string('payment_bank', 200);
			$table->string('payment_method', 200);
			$table->string('payment_account_number', 200);
			$table->string('payment_account_name', 200);
			$table->integer('payment_nominal');
			$table->string('payment_receipt_image');
			$table->string('payment_note');
			$table->string('payment_note_confirm')->nullable();
			$table->dateTime('confirmed_at')->nullable();
			$table->dateTime('cancelled_at')->nullable();
			$table->integer('id_user_confirming')->unsigned()->nullable()->index('fk_transaction_payments_users');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('transaction_payment_manuals');
	}

}
