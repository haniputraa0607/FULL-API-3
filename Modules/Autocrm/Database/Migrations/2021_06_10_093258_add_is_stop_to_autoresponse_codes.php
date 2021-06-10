<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsStopToAutoresponseCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autoresponse_codes', function (Blueprint $table) {
            $table->smallInteger('is_stop')->default(0)->nullable()->after('is_all_payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autoresponse_codes', function (Blueprint $table) {
            $table->dropColumn('is_stop');
        });
    }
}
