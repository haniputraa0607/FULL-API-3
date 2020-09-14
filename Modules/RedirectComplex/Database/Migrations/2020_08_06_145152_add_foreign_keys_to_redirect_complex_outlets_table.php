<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToRedirectComplexOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redirect_complex_outlets', function (Blueprint $table) {
        	$table->foreign('id_outlet', 'fk_redirect_complex_outlets_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('id_redirect_complex_reference', 'fk_redirect_complex_outlets_references')->references('id_redirect_complex_reference')->on('redirect_complex_references')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('redirect_complex_outlets', function (Blueprint $table) {
        	$table->dropForeign('fk_redirect_complex_outlets_outlets');
        	$table->dropForeign('fk_redirect_complex_outlets_references');
        });
    }
}
