<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFileNameToOutletCashAttachment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_cash_attachment', function (Blueprint $table) {
            $table->string('outlet_cash_attachment_name')->nullable()->after('id_outlet_cash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_cash_attachment', function (Blueprint $table) {
            $table->dropColumn('outlet_cash_attachment_name');
        });
    }
}
