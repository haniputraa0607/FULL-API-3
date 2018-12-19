<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionShipmentsTable extends Migration {

	public function up()
	{
		Schema::create('transaction_shipments', function(Blueprint $table)
		{
			$table->increments('id_transaction_shipment');
			$table->integer('id_transaction')->unsigned()->nullable()->index('fk_transaction_shipments_transactions');
			$table->string('depart_name', 200)->nullable();
			$table->string('depart_phone', 20)->nullable();
			$table->string('depart_address')->nullable();
			$table->integer('depart_id_city')->unsigned()->nullable()->index('fk_transaction_shipments_shipments_depart');
			$table->string('destination_name', 200)->nullable();
			$table->string('destination_phone', 20)->nullable();
			$table->string('destination_address')->nullable();
			$table->integer('destination_id_city')->unsigned()->nullable()->index('fk_transaction_shipments_shipments_destination');
			$table->string('destination_description')->nullable();
			$table->integer('shipment_total_weight')->nullable();
			$table->enum('shipment_courier', array('jne','pos','tiki','pcp','esl','rpx','pandu','wahana','sicepat','j&t','pahala','cahaya','sat','jet','indah','slis','dse','first','ncs','star'))->nullable();
			$table->string('shipment_courier_service', 200)->nullable();
			$table->string('shipment_courier_etd', 5)->nullable();
		});
	}

	public function down()
	{
		Schema::drop('transaction_shipments');
	}

}
