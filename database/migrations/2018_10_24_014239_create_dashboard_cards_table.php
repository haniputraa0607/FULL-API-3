<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDashboardCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_cards', function (Blueprint $table) {
            $table->increments('id_dashboard_card');
            $table->unsignedInteger('id_dashboard_user');
            $table->string('card_name');
            $table->smallInteger('card_order');
            $table->timestamps();

            $table->foreign('id_dashboard_user', 'fk_dashboard_cards_dashboard_users')->references('id_dashboard_user')->on('dashboard_users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dashboard_cards');
    }
}
