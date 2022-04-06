<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->bigIncrements('id_merchant');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->enum('merchant_status', ['Pending', 'In Progress', 'Active', 'Inactive', 'Rejected'])->default('Pending');
            $table->string('merchant_name', 250);
            $table->string('merchant_license_number')->nullable();
            $table->string('merchant_email')->nullable();
            $table->string('merchant_phone')->unique();
            $table->unsignedInteger('id_province');
            $table->unsignedInteger('id_city');
            $table->mediumText('merchant_address');
            $table->string('merchant_postal_code', 20)->nullable();
            $table->string('merchant_pic_name');
            $table->string('merchant_pic_id_card_number');
            $table->string('merchant_pic_email');
            $table->string('merchant_pic_phone');
            $table->smallInteger('merchant_completed_step')->default(0);
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
        Schema::dropIfExists('merchants');
    }
}
