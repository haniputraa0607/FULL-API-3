<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogIpay88sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('log_ipay88s', function (Blueprint $table) {
            $table->bigIncrements('id_log_ipay88');
            $table->string('type');
            $table->string('triggers');
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
        Schema::connection('mysql2')->dropIfExists('log_ipay88s');
    }
}
