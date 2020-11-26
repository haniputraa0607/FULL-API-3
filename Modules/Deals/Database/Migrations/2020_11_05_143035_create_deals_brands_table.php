<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_brands', function (Blueprint $table) {
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_brand');

            $table->foreign('id_deals')->on('deals')->references('id_deals')->onDelete('cascade');
            $table->foreign('id_brand')->on('brands')->references('id_brand')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_brands');
    }
}
