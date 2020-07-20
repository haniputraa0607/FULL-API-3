<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAchievementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achievement_details', function (Blueprint $table) {
            $table->bigIncrements('id_achievement_detail');
            $table->bigInteger('id_achievement_group')->unsigned();
            $table->string('name');
            $table->string('logo_badge');
            $table->integer('id_product')->nullable()->unsigned();
            $table->integer('product_total')->nullable();
            $table->integer('trx_nominal')->nullable();
            $table->integer('trx_total')->nullable();
            $table->integer('id_outlet')->nullable()->unsigned();
            $table->integer('id_province')->nullable()->unsigned();
            $table->integer('different_outlet')->nullable();
            $table->integer('different_province')->nullable();
            $table->timestamps();

            $table->foreign('id_achievement_group', 'fk_achievement_details_id_achievement_group')->references('id_achievement_group')->on('achievement_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_achievement_details_id_product')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_achievement_details_id_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_province', 'fk_achievement_details_id_province')->references('id_province')->on('provinces')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achievement_details');
    }
}
