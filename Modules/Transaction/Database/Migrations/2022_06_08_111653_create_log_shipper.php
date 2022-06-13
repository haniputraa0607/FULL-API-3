<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogShipper extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('log_shipper', function (Blueprint $table) {
            $table->bigIncrements('id_log_shipper');
            $table->string('subject')->nullable();
            $table->string('id_transaction')->nullable();
            $table->text('request')->nullable();
            $table->text('request_url')->nullable();
            $table->text('response')->nullable();
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
        Schema::connection('mysql2')->dropIfExists('log_shipper');
    }
}
