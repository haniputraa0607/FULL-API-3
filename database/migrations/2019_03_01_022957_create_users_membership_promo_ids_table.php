<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersMembershipPromoIdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_membership_promo_ids', function (Blueprint $table) {
            $table->increments('id_users_membership_promo_id');
            $table->unsignedInteger('id_users_membership');
            $table->string('promo_name')->nullable();
            $table->string('promo_id');
            $table->timestamps();

            $table->foreign('id_users_membership', 'fk_users_membership_promo_ids_users_memberships')
                ->references('id_log_membership')->on('users_memberships')
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
        Schema::dropIfExists('users_membership_promo_ids');
    }
}
