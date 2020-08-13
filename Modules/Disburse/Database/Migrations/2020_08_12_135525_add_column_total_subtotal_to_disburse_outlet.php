<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTotalSubtotalToDisburseOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse_outlet', function (Blueprint $table) {
            $table->decimal('total_promo_charged', 30, 4)->default(0)->after('total_omset');
            $table->decimal('total_subtotal', 30, 4)->default(0)->after('total_omset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse_outlet', function (Blueprint $table) {
            $table->dropColumn('total_promo_charged');
            $table->dropColumn('total_subtotal');
        });
    }
}
