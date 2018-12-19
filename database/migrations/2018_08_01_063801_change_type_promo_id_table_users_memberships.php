<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypePromoIdTableUsersMemberships extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_memberships', function (Blueprint $table) {
            DB::statement('ALTER TABLE `users_memberships` CHANGE `benefit_promo_id` `benefit_promo_id` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_memberships', function (Blueprint $table) {
            DB::statement('ALTER TABLE `users_memberships` CHANGE `benefit_promo_id` `benefit_promo_id` INT(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
        });
    }
}
