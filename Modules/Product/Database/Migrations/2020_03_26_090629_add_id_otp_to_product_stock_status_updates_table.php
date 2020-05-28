<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOtpToProductStockStatusUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_stock_status_updates', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet_app_otp')->nullable()->after('id_user');
            $table->foreign('id_outlet_app_otp')->references('id_outlet_app_otp')->on('outlet_app_otps')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_stock_status_updates', function (Blueprint $table) {
            $table->dropForeign('product_stock_status_updates_id_outlet_app_otp_foreign');
            $table->dropColumn('id_outlet_app_otp');
        });
    }
}
