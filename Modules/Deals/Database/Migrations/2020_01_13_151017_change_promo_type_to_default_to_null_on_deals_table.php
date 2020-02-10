<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePromoTypeToDefaultToNullOnDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::table('deals', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_promo_id_type deals_promo_id_type ENUM('promoid', 'nominal') DEFAULT NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_promo_id_type deals_promo_id_type ENUM('promoid', 'nominal') DEFAULT 'promoid'");
        });
    }
}
