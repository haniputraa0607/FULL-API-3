<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipPromoIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('membership_promo_ids', function (Blueprint $table) {
            $table->increments('id_membership_promo_id');
            $table->unsignedInteger('id_membership');
            $table->string('promo_name')->nullable();
            $table->string('promo_id');
            $table->timestamps();

            $table->foreign('id_membership', 'fk_membership_promo_ids_memberships')
                ->references('id_membership')->on('memberships')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('membership_promo_ids');
    }
}
