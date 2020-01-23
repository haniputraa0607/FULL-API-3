<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCodeWordingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_code_wording', function (Blueprint $table) {
            $table->increments('id_promo_code_wording');
            $table->enum('promo_type', ['Product discount', 'Tier discount', 'Buy X Get Y', 'Buy X Get Y 100%']);
            $table->string('wording');
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
        Schema::dropIfExists('promo_code_wording');
    }
}
