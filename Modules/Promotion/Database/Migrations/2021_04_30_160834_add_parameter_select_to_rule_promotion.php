<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParameterSelectToRulePromotion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->string('promotion_rule_param_select')->nullable()->after('promotion_rule_param');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->dropColumn('promotion_rule_param_select');
        });
    }
}
