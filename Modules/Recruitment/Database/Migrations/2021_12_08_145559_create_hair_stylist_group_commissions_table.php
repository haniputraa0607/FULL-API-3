<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairStylistGroupCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_commissions', function (Blueprint $table) {
            $table->integer('id_hairstylist_group')->unsigned();
            $table->foreign('id_hairstylist_group', 'fk_group_hairstylist_group_commissions')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->integer('id_product')->unsigned();
            $table->foreign('id_product', 'fk_products_hairstylist_group_commissions')->references('id_product')->on('products')->onDelete('restrict');
            $table->integer('commission_percent');
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
        Schema::dropIfExists('hairstylist_group_commissions');
    }
}
