<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdUserInNewsFormDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_form_datas', function (Blueprint $table) {
            $table->unsignedInteger('id_user')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('news_form_datas', function (Blueprint $table) {
            $table->unsignedInteger('id_user')->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
}
