<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('beneficiary_email', 200)->nullable()->after('outlet_different_price');
            $table->string('beneficiary_account', 200)->nullable()->after('outlet_different_price');
            $table->string('beneficiary_alias', 200)->nullable()->after('outlet_different_price');
            $table->string('beneficiary_name', 200)->nullable()->after('outlet_different_price');
            $table->integer('id_bank_name')->nullable()->after('outlet_different_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('beneficiary_email');
            $table->dropColumn('beneficiary_account');
            $table->dropColumn('beneficiary_alias');
            $table->dropColumn('beneficiary_name');
            $table->dropColumn('id_bank_name');
        });
    }
}
