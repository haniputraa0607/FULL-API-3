<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRedeemCounter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->integer('recipe_redemption_counter')->default(0)->nullable()->after('recipe_redemption_limit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->dropColumn('recipe_redemption_counter');
        });
    }
}
