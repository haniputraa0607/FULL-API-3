<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoresponseCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autoresponse_codes', function (Blueprint $table) {
            $table->integerIncrements('id_autoresponse_code');
            $table->string('autoresponse_code_name', 200);
            $table->date('autoresponse_code_periode_start');
            $table->date('autoresponse_code_periode_end');
            $table->smallInteger('is_all_transaction_type')->default(0)->nullable();
            $table->smallInteger('is_all_payment_method')->default(0)->nullable();
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
        Schema::dropIfExists('autoresponse_codes');
    }
}
