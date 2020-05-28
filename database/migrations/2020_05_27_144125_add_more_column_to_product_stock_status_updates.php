<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreColumnToProductStockStatusUpdates extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE `product_stock_status_updates` 
        ADD COLUMN `user_name` VARCHAR(45) NULL DEFAULT NULL AFTER `id_user`,
        ADD COLUMN `user_email` VARCHAR(45) NULL DEFAULT NULL AFTER `user_name`,
        CHANGE COLUMN `user_type` `user_type` ENUM(\'users\', \'user_outlets\', \'seeds\') NULL DEFAULT NULL ;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `product_stock_status_updates` 
        DROP COLUMN `user_email`,
        DROP COLUMN `user_name`,
        CHANGE COLUMN `user_type` `user_type` ENUM('users', 'user_outlets') NULL DEFAULT NULL ;");
    }
}
