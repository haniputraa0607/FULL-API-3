<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogApiSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_api_sms', function (Blueprint $table) {
            $table->bigIncrements('id_log_api_sms');
            $table->text('request_body')->nullable();
            $table->text('request_url')->nullable();
            $table->longText('response')->nullable();
            $table->string('phone',15)->nullable();
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
        Schema::dropIfExists('log_api_sms');
    }
}
