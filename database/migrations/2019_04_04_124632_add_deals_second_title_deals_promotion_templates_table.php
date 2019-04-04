<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDealsSecondTitleDealsPromotionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_promotion_templates', function (Blueprint $table) {
            $table->string('deals_second_title')->nullable()->after('deals_title');
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
            $table->dropColumn('deals_second_title');
        });
    }
}
