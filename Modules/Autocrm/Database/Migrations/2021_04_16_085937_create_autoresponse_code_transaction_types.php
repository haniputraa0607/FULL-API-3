<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoresponseCodeTransactionTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autoresponse_code_transaction_types', function (Blueprint $table) {
            $table->bigIncrements('id_autoresponse_code_transaction_type');
            $table->unsignedInteger('id_autoresponse_code');
            $table->string('autoresponse_code_transaction_type', 100);
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
        Schema::dropIfExists('autoresponse_code_transaction_types');
    }
}
