<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductModifierStockStatusUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_modifier_stock_status_updates', function (Blueprint $table) {
            $table->increments('id_product_modifier_stock_status_update');
            $table->dateTime('date_time');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->unsignedInteger('id_outlet_app_otp')->nullable();
            $table->enum('user_type',['users','user_outlets', 'seeds'])->nullable();
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_product_modifier');
            $table->enum('new_status',['Available','Sold Out']);
            $table->timestamps();

            $table->foreign('id_outlet_app_otp', 'fk_ioao_oao')->references('id_outlet_app_otp')->on('outlet_app_otps')->onDelete('set null');
            $table->foreign('id_outlet', 'fk_io_o')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product_modifier', 'fk_ipm_pm')->references('id_product_modifier')->on('product_modifiers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_modifier_stock_status_updates');
    }
}
