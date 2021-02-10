<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionOutletsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_outlets', function (Blueprint $table) {
            $table->integer('id_deals')->unsigned()->nullable();
			$table->integer('id_outlet')->unsigned()->nullable()->index('fk_deals_outlets_outlets');
			$table->index(['id_deals','id_outlet'], 'id_deals');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_outlets');
    }
}
