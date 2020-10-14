<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsAllShipmentAndIsAllPaymentMethodColumnToDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->boolean('is_all_shipment')->nullable()->after('deals_list_outlet');
        	$table->boolean('is_all_payment')->nullable()->after('is_all_shipment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->dropColumn('is_all_shipment');
        	$table->dropColumn('is_all_payment');
        });
    }
}
