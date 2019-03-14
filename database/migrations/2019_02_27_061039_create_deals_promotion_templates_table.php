<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_templates', function (Blueprint $table) {
            $table->increments('id_deals_promotion_template');
            $table->string('deals_title');
            $table->text('deals_description')->nullable();
            $table->string('deals_short_description')->nullable();
            $table->string('deals_image')->nullable();
			$table->enum('deals_voucher_type', array('Auto generated','List Vouchers', 'Unlimited'))->default('Auto generated');
			$table->enum('deals_promo_id_type', array('promoid', 'nominal'))->default('promoid');
			$table->char('deals_promo_id', 200)->nullable();
			$table->integer('deals_nominal')->nullable();
			$table->integer('deals_voucher_value');
			$table->integer('deals_voucher_given');
			$table->dateTime('deals_start');
			$table->dateTime('deals_end');
			$table->integer('deals_total_voucher')->nullable();
			$table->text('deals_list_voucher')->nullable();
			$table->integer('deals_voucher_duration')->nullable();
			$table->dateTime('deals_voucher_expired')->nullable();
			$table->text('deals_list_outlet');
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
        Schema::dropIfExists('deals_promotion_templates');
    }
}
