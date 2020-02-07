<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCreatedAtNullableToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `promo_campaigns` CHANGE COLUMN `created_by` `created_by` INT(11) NULL ,CHANGE COLUMN `last_updated_by` `last_updated_by` INT(11) NULL ;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `promo_campaigns` CHANGE COLUMN `created_by` `created_by` INT(11) NOT NULL ,CHANGE COLUMN `last_updated_by` `last_updated_by` INT(11) NOT NULL ;
");
    }
}
