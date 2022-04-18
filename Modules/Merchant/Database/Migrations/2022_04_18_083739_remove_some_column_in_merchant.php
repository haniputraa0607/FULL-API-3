<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSomeColumnInMerchant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('merchant_name');
            $table->dropColumn('merchant_license_number');
            $table->dropColumn('merchant_email');
            $table->dropColumn('merchant_phone');
            $table->dropColumn('id_province');
            $table->dropColumn('id_city');
            $table->dropColumn('merchant_address');
            $table->dropColumn('merchant_postal_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('merchant_name', 250);
            $table->string('merchant_license_number')->nullable();
            $table->string('merchant_email')->nullable();
            $table->string('merchant_phone')->unique();
            $table->unsignedInteger('id_province');
            $table->unsignedInteger('id_city');
            $table->mediumText('merchant_address');
            $table->string('merchant_postal_code', 20)->nullable();
        });
    }
}
