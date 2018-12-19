<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDealsPaymentMidtransTable extends Migration {

	public function up()
	{
		Schema::create('deals_payment_midtrans', function(Blueprint $table)
		{
			$table->increments('id_transaction_payment');
			$table->integer('id_deals')->unsigned()->index('fk_deals_payments_deals');
			$table->string('masked_card', 191)->nullable();
			$table->string('approval_code', 191)->nullable();
			$table->string('bank', 191)->nullable();
			$table->string('eci', 191)->nullable();
			$table->string('transaction_time', 191)->nullable();
			$table->string('gross_amount', 191);
			$table->string('order_id', 191);
			$table->string('payment_type', 191)->nullable();
			$table->string('signature_key', 191)->nullable();
			$table->string('status_code', 191)->nullable();
			$table->string('vt_transaction_id', 191)->nullable();
			$table->string('transaction_status', 191)->nullable();
			$table->string('fraud_status', 191)->nullable();
			$table->string('status_message', 191)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('deals_payment_midtrans');
	}

}
