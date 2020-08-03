<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChargedCentralAndChargedOutletToDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
            $table->decimal('charged_central', 5,2)->nullable()->after('custom_outlet_text');
            $table->decimal('charged_outlet', 5,2)->nullable()->after('custom_outlet_text');
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
            $table->dropColumn('charged_central');
            $table->dropColumn('charged_outlet');

        });
    }
}
