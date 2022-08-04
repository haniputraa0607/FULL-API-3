<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveFkOutletAndBrandEnquiries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropForeign('fk_enquiries_brand');
            $table->dropForeign('fk_enquiries_outlets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->foreign('id_brand', 'fk_enquiries_brand')->references('id_brand')->on('brands')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_enquiries_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
