<?php

use Illuminate\Database\Seeder;

class TextReplacesTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('text_replaces')->delete();
        
        \DB::table('text_replaces')->insert(array (
            0 => 
            array (
                'id_text_replace' => 1,
                'keyword' => '%phone%',
                'reference' => 'phone',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:47:42',
                'updated_at' => '2018-03-12 14:47:43',
            ),
            1 => 
            array (
                'id_text_replace' => 2,
                'keyword' => '%name%',
                'reference' => 'name',
                'type' => 'String',
                'default_value' => 'Customer',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:47:42',
                'updated_at' => '2018-03-22 09:18:06',
            ),
            2 => 
            array (
                'id_text_replace' => 3,
                'keyword' => '%email%',
                'reference' => 'email',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:47:42',
                'updated_at' => '2018-03-12 14:47:43',
            ),
            3 => 
            array (
                'id_text_replace' => 4,
                'keyword' => '%gender%',
                'reference' => 'gender',
                'type' => 'Alias',
                'default_value' => '[unknown gender]',
                'custom_rule' => 'Male=Pria;Female=Wanita',
                'status' => 'Activated',
                'created_at' => '2018-03-13 08:58:12',
                'updated_at' => '2018-03-13 08:58:13',
            ),
            4 => 
            array (
                'id_text_replace' => 5,
                'keyword' => '%title%',
                'reference' => 'gender',
                'type' => 'Alias',
                'default_value' => 'Mr',
                'custom_rule' => 'Male=Mr;Female=Mrs',
                'status' => 'Activated',
                'created_at' => '2018-03-13 08:58:12',
                'updated_at' => '2018-03-13 08:58:13',
            ),
            5 => 
            array (
                'id_text_replace' => 6,
                'keyword' => '%city%',
                'reference' => 'city_name',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-13 08:57:28',
                'updated_at' => '2018-03-13 08:57:29',
            ),
            6 => 
            array (
                'id_text_replace' => 7,
                'keyword' => '%province%',
                'reference' => 'province_name',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:57:08',
                'updated_at' => '2018-03-13 09:57:10',
            ),
            7 => 
            array (
                'id_text_replace' => 8,
                'keyword' => '%phone_provider%',
                'reference' => 'provider',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Not Activated',
                'created_at' => '2018-03-13 09:21:51',
                'updated_at' => '2018-03-13 09:21:55',
            ),
            8 => 
            array (
                'id_text_replace' => 9,
                'keyword' => '%birthday%',
                'reference' => 'birthday',
                'type' => 'Date',
                'default_value' => NULL,
                'custom_rule' => 'd F Y',
                'status' => 'Activated',
                'created_at' => '2018-03-13 08:58:12',
                'updated_at' => '2018-03-13 08:58:13',
            ),
            9 => 
            array (
                'id_text_replace' => 10,
                'keyword' => '%level%',
                'reference' => 'level',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:36:19',
                'updated_at' => '2018-03-13 09:36:21',
            ),
            10 => 
            array (
                'id_text_replace' => 11,
                'keyword' => '%points%',
                'reference' => 'points',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:57:08',
                'updated_at' => '2018-03-13 09:57:10',
            ),
            11 => 
            array (
                'id_text_replace' => 12,
                'keyword' => '%phone_verify_status%',
                'reference' => 'phone_verified',
                'type' => 'Alias',
                'default_value' => '[unknown status]',
                'custom_rule' => '0=Not Verified;1=Verified',
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:36:19',
                'updated_at' => '2018-03-13 09:36:21',
            ),
            12 => 
            array (
                'id_text_replace' => 13,
                'keyword' => '%email_verify_status%',
                'reference' => 'email_verified',
                'type' => 'Alias',
                'default_value' => '[unknown status]',
                'custom_rule' => '0=Not Verified;1=Verified',
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:36:19',
                'updated_at' => '2018-03-13 09:36:21',
            ),
            13 => 
            array (
                'id_text_replace' => 14,
                'keyword' => '%email_status%',
                'reference' => 'email_unsubscribed',
                'type' => 'Alias',
                'default_value' => 'Subscribed',
                'custom_rule' => '0=Subscribed;1=Unsubscribed',
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:53:06',
                'updated_at' => '2018-03-13 09:53:08',
            ),
            14 => 
            array (
                'id_text_replace' => 15,
                'keyword' => '%suspend_status%',
                'reference' => 'is_suspended',
                'type' => 'Alias',
                'default_value' => 'Active',
                'custom_rule' => '0=Active;1=Suspended',
                'status' => 'Activated',
                'created_at' => '2018-03-13 09:57:08',
                'updated_at' => '2018-03-13 09:57:10',
            ),
            15 => 
            array (
                'id_text_replace' => 16,
                'keyword' => '%register_time%',
                'reference' => 'created_at',
                'type' => 'DateTime',
                'default_value' => NULL,
                'custom_rule' => 'd F Y H:i',
                'status' => 'Activated',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            16 => 
            array (
                'id_text_replace' => 17,
                'keyword' => '%ip%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[ip address not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:58:55',
                'updated_at' => '2018-03-12 14:58:56',
            ),
            17 => 
            array (
                'id_text_replace' => 18,
                'keyword' => '%useragent%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[useragent not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:58:55',
                'updated_at' => '2018-03-12 14:58:56',
            ),
            18 => 
            array (
                'id_text_replace' => 19,
                'keyword' => '%now%',
                'reference' => 'variables',
                'type' => 'DateTime',
                'default_value' => 'Y-m-d H:i:s',
                'custom_rule' => 'd F Y H:i',
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:58:55',
                'updated_at' => '2018-03-12 14:58:56',
            ),
            19 => 
            array (
                'id_text_replace' => 20,
                'keyword' => '%pin%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => NULL,
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => '2018-03-12 14:58:55',
                'updated_at' => '2018-03-12 14:58:56',
            ),
            20 => 
            array (
                'id_text_replace' => 21,
                'keyword' => '%enquiry_subject%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[message not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            21 => 
            array (
                'id_text_replace' => 22,
                'keyword' => '%enquiry_message%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[message not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            22 => 
            array (
                'id_text_replace' => 23,
                'keyword' => '%enquiry_phone%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[message not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            23 => 
            array (
                'id_text_replace' => 24,
                'keyword' => '%enquiry_name%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[message not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            24 => 
            array (
                'id_text_replace' => 27,
                'keyword' => '%enquiry_email%',
                'reference' => 'variables',
                'type' => 'String',
                'default_value' => '[message not found]',
                'custom_rule' => NULL,
                'status' => 'Activated',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}