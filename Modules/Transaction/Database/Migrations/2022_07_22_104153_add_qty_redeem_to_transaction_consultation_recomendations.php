<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQtyRedeemToTransactionConsultationRecomendations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultation_recomendations', function (Blueprint $table) {
            $table->integer('qty_product_redeem')->default(0)->nullable()->after('qty_product');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultation_recomendations', function (Blueprint $table) {
            $table->dropColumn('qty_product_redeem');
        });
    }
}
