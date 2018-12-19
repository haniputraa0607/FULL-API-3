<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWhatsappTypeInPromotionContentShortenLinksTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    public function up()
    {
        DB::statement('ALTER TABLE `promotion_content_shorten_links` MODIFY COLUMN `type` ENUM("email","sms","push_notification","inbox","whatsapp")');
    }
    
    public function down()
    {
        DB::statement('ALTER TABLE `promotion_content_shorten_links` MODIFY COLUMN `type` ENUM("email","sms","push_notification","inbox")');
    }
}
