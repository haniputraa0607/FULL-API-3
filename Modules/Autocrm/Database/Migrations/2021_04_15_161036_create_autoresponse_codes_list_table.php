<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoresponseCodesListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autoresponse_code_list', function (Blueprint $table) {
            $table->bigIncrements('id_autoresponse_code_list');
            $table->unsignedInteger('id_autoresponse_code');
            $table->string('autoresponse_code', 200);
            $table->unsignedInteger('id_user')->nullable();
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
        Schema::dropIfExists('autoresponse_code_list');
    }
}
