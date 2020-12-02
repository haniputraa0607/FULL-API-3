<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductTypeToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->enum('product_type', ['single', 'variant'])->after('promo_type')->default('single');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->dropColumn('product_type');
        });
    }
}
