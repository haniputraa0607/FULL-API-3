<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_memberships', function (Blueprint $table) {
           $table->increments('id_special_membership');
			$table->string('special_membership_name');
			$table->string('payment_method',200)->nullable();
			$table->decimal('benefit_point_multiplier', 10)->nullable();
			$table->decimal('benefit_cashback_multiplier', 10)->nullable();
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
        Schema::dropIfExists('special_memberships');
    }
}
