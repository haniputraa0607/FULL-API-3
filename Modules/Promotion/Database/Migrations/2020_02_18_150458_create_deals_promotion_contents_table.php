<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_contents', function (Blueprint $table) {
            $table->increments('id_deals_content');
            $table->integer('id_deals')->unsigned();
            $table->string('title', 50);
            $table->smallInteger('order');
            $table->boolean('is_active')->nullable()->default(1);

            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_promotion_templates_deals_promotion_contents')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_contents');
    }
}
