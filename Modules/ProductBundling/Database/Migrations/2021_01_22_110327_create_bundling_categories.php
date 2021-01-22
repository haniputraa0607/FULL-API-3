<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundlingCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundling_categories', function (Blueprint $table) {
            $table->integerIncrements('id_bundling_category');
            $table->integer('id_parent_category')->default(0);
            $table->string('bundling_category_name', 200);
            $table->mediumText('bundling_category_description')->nullable();
            $table->integer('bundling_category_order')->nullable();
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
        Schema::dropIfExists('bundling_categories');
    }
}
