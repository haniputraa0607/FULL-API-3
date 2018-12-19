<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubscriptionDealsInDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_type deals_type ENUM('Deals', 'Hidden', 'Point', 'Spin', 'Subscription') NOT NULL DEFAULT 'Deals'");
            $table->string('total_voucher_subscription')->nullable()->comment('total voucher in deals subscription')->after('deals_total_voucher');
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_promo_id_type deals_promo_id_type ENUM('promoid', 'nominal') NULL DEFAULT 'promoid'");
            DB::statement("ALTER TABLE deals MODIFY COLUMN deals_promo_id CHAR(200)");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_type deals_type ENUM('Deals', 'Hidden', 'Point', 'Spin') NOT NULL DEFAULT 'Deals'");
            $table->dropColumn('total_voucher_subscription');
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_promo_id_type deals_promo_id_type ENUM('promoid', 'nominal') NOT NULL DEFAULT 'promoid'");
            DB::statement("ALTER TABLE deals MODIFY COLUMN deals_promo_id CHAR(200) NOT NULL");
        });
    }
}
