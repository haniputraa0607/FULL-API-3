<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->increments('id_promotion');
			$table->string('promotion_name');
			$table->enum('promotion_type',['Instant Campaign','Scheduled Campaign','Recurring Campaign','Campaign Series'])->default('Instant Campaign');
			$table->enum('promotion_vouchers',['Yes','No'])->default('No');
			$table->tinyInteger('promotion_series')->default(1);
			$table->tinyInteger('promotion_queue_priority');
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
        Schema::dropIfExists('promotions');
    }
}
