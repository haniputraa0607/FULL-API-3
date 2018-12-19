<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPickupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_pickups', function (Blueprint $table) {
            $table->increments('id_transaction_pickup');
			$table->integer('id_transaction')->unsigned()->index('fk_transaction_pickups_transaction');
            $table->string('order_id', 4);
            $table->string('short_link');
            $table->timestamp('receive_at')->nullable();
            $table->timestamp('taken_at')->nullable();
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
        Schema::dropIfExists('transaction_pickups');
    }
}
