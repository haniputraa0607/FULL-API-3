<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionProductDiscountRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_product_discount_rules', function (Blueprint $table) {
            $table->increments('id_deals_product_discount_rule');
            $table->unsignedInteger('id_deals');
            $table->char('is_all_product', 1);
            $table->enum('discount_type', ['Percent', 'Nominal']);
            $table->integer('discount_value');
            $table->integer('max_percent_discount')->nullable();
            $table->integer('max_product')->default(0);
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_promotion_product_discount_rules_deals')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_product_discount_rules');
    }
}
