<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogMidtransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('log_midtrans', function (Blueprint $table) {
            $table->bigIncrements('id_log_midtrans');
            $table->string('type')->nullable();
            $table->string('id_reference')->nullable();
            $table->text('request')->nullable();
            $table->text('request_header')->nullable();
            $table->text('request_url')->nullable();
            $table->text('response')->nullable();
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
        Schema::dropIfExists('log_midtrans');
    }
}
