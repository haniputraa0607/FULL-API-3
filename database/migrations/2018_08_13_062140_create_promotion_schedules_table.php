<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_schedules', function (Blueprint $table) {
            $table->increments('id_promotion_schedule');
            $table->integer('id_promotion')->unsigned()->index('fk_promotion_schedules_promotions');
            $table->time('schedule_time')->nullable()->default(null);
            $table->date('schedule_exact_date')->nullable()->default(null);
            $table->string('schedule_date_month',5)->nullable()->default(null);
            $table->integer('schedule_date_every_month')->nullable()->default(null);
            $table->enum('schedule_day_every_week',['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'])->nullable()->default(null);
            $table->integer('schedule_week_in_month')->nullable()->default(null);
            $table->enum('schedule_everyday',['Yes','No'])->nullable()->default(null);
			$table->timestamps();
        });
		
		Schema::table('promotion_schedules', function (Blueprint $table) {
			$table->foreign('id_promotion', 'fk_promotion_schedules_promotions')->references('id_promotion')->on('promotions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('promotion_schedules', function (Blueprint $table) {
			$table->dropForeign('fk_promotion_schedules_promotions');
		});
		
        Schema::dropIfExists('promotion_schedules');
    }
}
