<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterIdInboxGlobalRuleAiPkInboxGlobalRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::connection('mysql')->statement('ALTER TABLE `inbox_global_rules` 
        CHANGE COLUMN `id_inbox_global_rule` `id_inbox_global_rule` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
        ADD PRIMARY KEY (`id_inbox_global_rule`);
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::connection('mysql')->statement('ALTER TABLE `sapicham_db`.`inbox_global_rules` CHANGE COLUMN `id_inbox_global_rule` `id_inbox_global_rule` INT(10) UNSIGNED NOT NULL , DROP PRIMARY KEY;');
    }
}
