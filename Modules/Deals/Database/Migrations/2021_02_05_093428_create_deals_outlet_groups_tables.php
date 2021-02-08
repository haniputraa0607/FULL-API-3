<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsOutletGroupsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_outlet_groups', function (Blueprint $table) {
            $table->bigIncrements('id_deals_outlet_group');
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_outlet_group');
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_outlet_groups_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet_group', 'fk_deals_outlet_groups_outlet_groups')->references('id_outlet_group')->on('outlet_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_outlet_groups');
    }
}
