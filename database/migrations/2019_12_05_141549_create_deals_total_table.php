<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsTotalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_total', function (Blueprint $table) {
            $table->bigIncrements('id_deals_total');
            $table->unsignedInteger('id_deals');
            $table->integer('deals_total')->nullable();
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_total_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_total');
    }
}
