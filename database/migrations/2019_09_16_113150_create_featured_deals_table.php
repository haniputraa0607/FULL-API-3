<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeaturedDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('featured_deals', function (Blueprint $table) {
            $table->increments('id_featured_deals');
            $table->unsignedInteger('id_deals');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->unsignedInteger('order');
            $table->timestamps();

            $table->foreign('id_deals','fk_featured_deals_id_deals_foreign')->references('id_deals')->on('deals')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('featured_deals');
    }
}
