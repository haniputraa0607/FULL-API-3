<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPromotionUserLimitPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotions', function(Blueprint $table) {
            $table->char('promotion_user_limit', '1')->default('0')->after('promotion_queue_priority');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotions', function(Blueprint $table) {
            $table->dropColumn('promotion_user_limit');
        });
    }
}
