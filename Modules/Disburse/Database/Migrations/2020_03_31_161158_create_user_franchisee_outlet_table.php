<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFranchiseeOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_franchise_outlet', function (Blueprint $table) {
            $table->bigIncrements('id_user_franchise_outlet');
            $table->integer('id_user_franchise');
            $table->integer('id_outlet');
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
        Schema::dropIfExists('user_franchise_outlet');
    }
}
