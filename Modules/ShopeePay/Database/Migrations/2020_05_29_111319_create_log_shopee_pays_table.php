<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogShopeePaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('log_shopee_pays', function (Blueprint $table) {
            $table->bigIncrements('id_log_shopee_pay');
            $table->string('type');
            $table->string('id_reference');
            $table->text('request');
            $table->text('request_header');
            $table->text('request_url');
            $table->text('response');
            $table->string('response_status_code')->nullable();
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
        Schema::dropIfExists('log_shopee_pays');
    }
}
