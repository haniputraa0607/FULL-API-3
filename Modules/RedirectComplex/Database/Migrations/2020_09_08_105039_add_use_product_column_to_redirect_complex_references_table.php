<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUseProductColumnToRedirectComplexReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redirect_complex_references', function (Blueprint $table) {
        	$table->boolean('use_product')->nullable()->default(0)->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('redirect_complex_references', function (Blueprint $table) {
        	$table->dropColumn('use_product');
        });
    }
}
