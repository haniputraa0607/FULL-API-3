<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomPageOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_page_outlets', function (Blueprint $table) {
            $table->unsignedInteger('id_custom_page');
            $table->unsignedInteger('id_outlet');
            $table->timestamps();

            $table->foreign('id_custom_page', 'fk_custom_page_outlets_id_custom_page')->references('id_custom_page')->on('custom_pages')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_custom_page_outlets_id_outlet')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_page_outlets');
    }
}
