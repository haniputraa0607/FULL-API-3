<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDealsTableByRevisi extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('deals_video');
            $table->dropColumn('deals_voucher_type');
            $table->enum('deals_promo_id_type', ['promoid','nominal'])->default('promoid')->after('deals_voucher_type');

        });
        Schema::table('deals', function (Blueprint $table) {
            $table->enum('deals_voucher_type', ['Auto generated','List Vouchers','Unlimited'])->after('deals_type');
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
            $table->string('deals_video', 100)->nullable();
            $table->dropColumn('deals_voucher_type');
            $table->dropColumn('deals_promo_id_type');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->enum('deals_voucher_type', ['Auto generated','List Vouchers'])->after('deals_type');
        });
    }
}