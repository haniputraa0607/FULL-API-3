<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOutletInUserLocationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_location_details', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet')->nullable()->after('id_reference');
            $table->string('outlet_code')->nullable()->after('id_outlet');
            $table->string('outlet_name')->nullable()->after('outlet_code');

            $table->foreign('id_outlet', 'fk_user_location_details_outlets')
            ->references('id_outlet')->on('outlets')
            ->onDelete('cascade')
            ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_location_details', function (Blueprint $table) {
            $table->dropForeign('fk_promotion_contents_deals_promotion_templates');
            $table->dropColumn('id_outlet');
            $table->dropColumn('outlet_code');
            $table->dropColumn('outlet_name');
        });
    }
}
