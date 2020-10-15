<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionTypeColumnToRedirectComplexReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('redirect_complex_references', function (Blueprint $table) {
        	$table->string('transaction_type')->after('use_product')->nullable();
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
        	$table->dropColumn('transaction_type');
        });
    }
}
