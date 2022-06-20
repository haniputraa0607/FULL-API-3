<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCashattachment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlet_cash', function (Blueprint $table) {
            $table->dropColumn('outlet_cash_attachement');
        });
        Schema::create('outlet_cash_attachment', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_cash_attachment');
            $table->unsignedInteger('id_outlet_cash');
            $table->string('outlet_cash_attachment', 250);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_cash', function (Blueprint $table) {
            $table->string('outlet_cash_attachement', 250)->nullable();
        });
        Schema::dropIfExists('outlet_cash_attachment');
    }
}
