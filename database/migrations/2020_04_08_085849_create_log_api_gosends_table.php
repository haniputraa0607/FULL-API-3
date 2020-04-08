<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogApiGosendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('log_api_gosends', function (Blueprint $table) {
            $table->bigIncrements('id_log_api_gosend');
            $table->string('type')->nullable();
            $table->string('id_reference')->nullable();
            $table->string('request_url')->nullable();
            $table->string('request_method')->nullable();
            $table->text('request_parameter')->nullable();
            $table->string('response_code')->nullable();
            $table->text('response_body')->nullable();
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
        Schema::dropIfExists('log_api_gosends');
    }
}
