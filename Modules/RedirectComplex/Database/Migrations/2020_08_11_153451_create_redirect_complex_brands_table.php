<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedirectComplexBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redirect_complex_brands', function (Blueprint $table) {
            $table->increments('id_redirect_complex_brand');
            $table->integer('id_redirect_complex_reference')->unsigned()->index('fk_redirect_complex_brands_reference');
			$table->integer('id_brand')->unsigned()->index('fk_redirect_complex_brands_brands');
            $table->timestamps();

            $table->foreign('id_brand', 'fk_redirect_complex_brands_brands')->references('id_brand')->on('brands')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('id_redirect_complex_reference', 'fk_redirect_complex_brands_references')->references('id_redirect_complex_reference')->on('redirect_complex_references')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('redirect_complex_brands');
    }
}
