<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLogInvalidTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_invalid_transactions', function (Blueprint $table) {
            $table->bigIncrements('id_log_invalid_transaction');
            $table->integer('id_transaction');
            $table->enum('tansaction_flag', ['Valid', 'Invalid'])->nullable();
            $table->string('updated_by', 250)->nullable();
            $table->dateTime('updated_date')->nullable();
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
        Schema::dropIfExists('log_invalid_transactions');
    }
}
