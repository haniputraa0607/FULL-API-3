<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOutletAppOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::unprepared('ALTER TABLE `outlet_app_otps` 
            DROP FOREIGN KEY `outlet_app_otps_id_outlet_foreign`,
            DROP FOREIGN KEY `outlet_app_otps_id_user_outlet_foreign`;
            ALTER TABLE `outlet_app_otps` 
            ADD CONSTRAINT `outlet_app_otps_id_outlet_foreign`
              FOREIGN KEY (`id_outlet`)
              REFERENCES `outlets` (`id_outlet`)
              ON DELETE CASCADE
              ON UPDATE RESTRICT,
            ADD CONSTRAINT `outlet_app_otps_id_user_outlet_foreign`
              FOREIGN KEY (`id_user_outlet`)
              REFERENCES `user_outlets` (`id_user_outlet`)
              ON DELETE CASCADE;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::unprepared('ALTER TABLE `outlet_app_otps` 
            DROP FOREIGN KEY `outlet_app_otps_id_outlet_foreign`,
            DROP FOREIGN KEY `outlet_app_otps_id_user_outlet_foreign`;
            ALTER TABLE `outlet_app_otps` 
            ADD CONSTRAINT `outlet_app_otps_id_outlet_foreign`
              FOREIGN KEY (`id_outlet`)
              REFERENCES `outlets` (`id_outlet`)
              ON DELETE RESTRICT
              ON UPDATE RESTRICT,
            ADD CONSTRAINT `outlet_app_otps_id_user_outlet_foreign`
              FOREIGN KEY (`id_user_outlet`)
              REFERENCES `user_outlets` (`id_user_outlet`)
              ON DELETE RESTRICT;
        ');
    }
}
