<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBundlingPromoStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->smallInteger('bundling_promo_status')->default(0)->after('bundling_name');
            $table->enum('bundling_specific_day_type', ['Day', 'Date'])->nullable()->after('bundling_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->dropColumn('bundling_promo_status');
            $table->dropColumn('bundling_specific_day_type');
        });
    }
}
