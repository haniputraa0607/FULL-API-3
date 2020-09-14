<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandColumnToRedirectComplexProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redirect_complex_products', function (Blueprint $table) {
        	$table->integer('id_brand')->after('id_redirect_complex_reference')->unsigned()->index('fk_redirect_complex_products_brands');
        	$table->foreign('id_brand', 'fk_redirect_complex_products_brands')->references('id_brand')->on('brands')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('redirect_complex_products', function (Blueprint $table) {
        	$table->dropForeign('fk_redirect_complex_products_brands');
        	$table->dropColumn('id_brand');
        });
    }
}
