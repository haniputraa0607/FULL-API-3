<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateQuestDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quest_details', function (Blueprint $table) {
            $table->string('short_description')->after('name');
            $table->integer('id_product_category')->after('short_description')->unsigned()->nullable();
            $table->integer('different_category_product')->after('id_product_category')->nullable();

            $table->foreign('id_product_category', 'fk_quest_details_id_product_category')->references('id_product_category')->on('product_categories')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {
        });
    }
}
