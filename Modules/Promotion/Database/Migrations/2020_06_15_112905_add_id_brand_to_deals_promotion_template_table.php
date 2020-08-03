<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdBrandToDealsPromotionTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
        	$table->unsignedInteger('id_brand')->nullable()->after('last_updated_by');

            $table->foreign('id_brand','fk_deals_promotion_template_id_brand')->references('id_brand')->on('brands')->onDelete('CASCADE');
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
        	$table->dropForeign('fk_deals_promotion_template_id_brand');
            $table->dropColumn('id_brand');
        });
    }
}
