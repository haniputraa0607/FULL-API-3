<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_contents', function (Blueprint $table) {
            $table->increments('id_deals_content');
            $table->integer('id_deals')->unsigned();
            $table->string('title', 50);
            $table->smallInteger('order');
            $table->boolean('is_active')->nullable()->default(1);

            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_deals_contents')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_contents');
    }
}
