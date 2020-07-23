<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionContentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_content_details', function (Blueprint $table) {

            $table->increments('id_deals_content_detail');
            $table->integer('id_deals_content')->unsigned();
            $table->text('content', 65535);
            $table->smallInteger('order');

            $table->timestamps();

            $table->foreign('id_deals_content', 'fk_deals_promotion_contents_deals_content_details')->references('id_deals_content')->on('deals_promotion_contents')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_content_details');
    }
}
