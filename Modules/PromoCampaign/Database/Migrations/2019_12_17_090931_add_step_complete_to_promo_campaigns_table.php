<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStepCompleteToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
            $table->boolean('step_complete')->nullable()->after('promo_type');
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
            $table->dropColumn('step_complete');
        });
    }
}
