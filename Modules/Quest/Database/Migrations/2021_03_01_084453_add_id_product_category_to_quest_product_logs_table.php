<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdProductCategoryToQuestProductLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quest_product_logs', function (Blueprint $table) {
            $table->unsignedInteger('id_product_category')->after('id_product')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quest_product_logs', function (Blueprint $table) {
            $table->dropColumn('id_product_category');
        });
    }
}
