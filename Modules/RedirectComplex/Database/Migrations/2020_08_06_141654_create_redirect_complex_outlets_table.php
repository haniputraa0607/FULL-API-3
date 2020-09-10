<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedirectComplexOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redirect_complex_outlets', function (Blueprint $table) {
        	$table->increments('id_redirect_complex_outlet');
            $table->integer('id_redirect_complex_reference')->unsigned()->index('fk_redirect_complex_outlets_reference');
			$table->integer('id_outlet')->unsigned()->index('fk_redirect_complex_outlets_outlets');
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
        Schema::dropIfExists('redirect_complex_outlets');
    }
}
