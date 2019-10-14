<?php

use Illuminate\Database\Seeder;

class AutocrmsTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('autocrms')->delete();
        
        \DB::table('autocrms')->insert(array (
            0 => 
            array (
                'id_autocrm' => 1,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Login Success',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'Login Success in Your Natasha account',
                'autocrm_email_content' => 'Hello %name%, You are recently logged in Your Account:
<br><br>
Time: %now%<br>
IP Address: %ip%<br>
Device: %useragent%
<br><br>
If this is not You, please change Your password immidiately!',
                'autocrm_sms_content' => 'Hello, This is SMS. Login success detected',
                'autocrm_push_subject' => 'Hello, This is Push Notification Subject. Login success detected',
                'autocrm_push_content' => 'Halo, Anda sudah login ke account Natasha Anda.',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => 'Home',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Hello, This is Inbox Notification Subject. Login success detected',
                'autocrm_inbox_content' => 'Hello, This is Inbox Notification Subject. Login success detected',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'Login detected',
            'autocrm_forward_email_content' => '<p>There are login at account %title%,%name% (%phone%):</p><p>Time: %now%<br>
IP Address: %ip%<br>
Device: %useragent%
</p><p>City: %city%</p><p>Level: %level%</p><p>Birthday: %birthday%</p><p>Points: %points%</p><p>Now: %now%</p><p>%useragent%<br></p>',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:01:56',
            ),
            1 => 
            array (
                'id_autocrm' => 2,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Login Failed',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'Login Failed in Your Natasha account',
                'autocrm_email_content' => 'Hello %name%, There are an attempt to login in Your Account:
<br><br>
Time: %now%<br>
IP Address: %ip%<br>
Device: %useragent%
<br><br>
The login is failed. Make sure Your password is safe.',
                'autocrm_sms_content' => 'Hello, This is SMS. Login failed detected',
                'autocrm_push_subject' => 'Hello, This is Push Notification Subject. Login failed detected',
                'autocrm_push_content' => 'Hello, This is Push Notification Content. Login failed detected',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => 'Home',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Ini subjectnya',
                'autocrm_inbox_content' => '<p>Test wkwkwkw, Hello %name%<br></p>',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'Failed to Login',
                'autocrm_forward_email_content' => '<p>Failed to Login<br></p>',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:02:00',
            ),
            2 => 
            array (
                'id_autocrm' => 3,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Pin Sent',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => 'Hello, Bro Your PIN is %pin%',
                'autocrm_email_content' => 'Hello, Your PIN is %pin%',
                'autocrm_sms_content' => 'Hello, Your PIN is %pin%',
                'autocrm_push_subject' => NULL,
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => NULL,
                'autocrm_inbox_content' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:02:05',
            ),
            3 => 
            array (
                'id_autocrm' => 4,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Pin Changed',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => 'Hello, Your PIN is was changed',
                'autocrm_email_content' => 'Hello, Your PIN is was changed',
                'autocrm_sms_content' => 'Hello, Your PIN is was changed',
                'autocrm_push_subject' => NULL,
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => NULL,
                'autocrm_inbox_content' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:02:10',
            ),
            4 => 
            array (
                'id_autocrm' => 5,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Pin Verify',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => 'Hello, Your Phone Number is Verified',
                'autocrm_email_content' => 'Hello, Your Phone Number is Verified',
                'autocrm_sms_content' => 'Hello, Your Phone Number is Verified',
                'autocrm_push_subject' => NULL,
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => NULL,
                'autocrm_inbox_content' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:02:14',
            ),
            5 => 
            array (
                'id_autocrm' => 6,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Transaction Success',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'Transaction Success',
                'autocrm_email_content' => 'Transaction Success',
                'autocrm_sms_content' => 'Hello, Your Phone Number is Verified',
                'autocrm_push_subject' => NULL,
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => NULL,
                'autocrm_inbox_content' => NULL,
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'There is a Transaction Success',
                'autocrm_forward_email_content' => 'There is a Transaction Success',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:02:20',
            ),
            6 => 
            array (
                'id_autocrm' => 7,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Enquiry Question',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'We have receive Your Question',
                'autocrm_email_content' => 'We have receive Your Question
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%',
                'autocrm_sms_content' => NULL,
                'autocrm_push_subject' => 'We have receive Your Question',
                'autocrm_push_content' => 'Enquiry',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Natasha Enquiry',
                'autocrm_inbox_content' => 'There is an Inbox Enquiry Question
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'There is an Email Enquiry Question',
                'autocrm_forward_email_content' => '<p>There is an Email Enquiry Question
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%</p><p>Please respond to this customer within 24 hours.<br></p>',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:19',
            ),
            7 => 
            array (
                'id_autocrm' => 8,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Enquiry Complaint',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'We have receive Your Complaint',
                'autocrm_email_content' => 'We have receive Your Complaint
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%',
                'autocrm_sms_content' => NULL,
                'autocrm_push_subject' => 'Complaint Received',
                'autocrm_push_content' => ' Enquiry Complaint Content',
                'autocrm_push_image' => 'img/push/2021524214254.jpg',
                'autocrm_push_clickto' => 'Product',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Natasha Enquiry',
                'autocrm_inbox_content' => 'There is an Inbox Enquiry Complaint
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'There is an Email Enquiry Complaint',
                'autocrm_forward_email_content' => '<p>There is an Email Enquiry Complaint
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%</p><p>Please respond to this customer within 24 hours.<br></p>',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-27 09:55:11',
            ),
            8 => 
            array (
                'id_autocrm' => 9,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Enquiry Partnership',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'We have receive Your Partnership',
                'autocrm_email_content' => 'We have receive Your Partnership
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%',
                'autocrm_sms_content' => 'We have receive Your Partnerhip',
                'autocrm_push_subject' => 'Natasha Enquiry',
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Natasha Enquiry',
                'autocrm_inbox_content' => 'There is an Inbox Enquiry Partnerhip
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'There is an Email Enquiry Partnership',
                'autocrm_forward_email_content' => '<p>There is an Email Enquiry Partnerhip
<br>
<br>
From :
<br>
%enquiry_name% - %enquiry_phone% - %enquiry_email%
<br>
<br>
Subject :
<br>
%enquiry_subject%
<br>
<br>
Message : 
<br>
%enquiry_message%</p><p>Please respond to this customer within 24 hours.<br></p>',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            9 => 
            array (
                'id_autocrm' => 10,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Deals',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'New Deals',
                'autocrm_email_content' => 'New Deals',
                'autocrm_sms_content' => 'New Deals',
                'autocrm_push_subject' => 'New Deals',
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'New Deals',
                'autocrm_inbox_content' => 'New Deals',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'New Deals',
                'autocrm_forward_email_content' => 'New Deals',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            10 => 
            array (
                'id_autocrm' => 11,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Order Ready',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => NULL,
                'autocrm_email_content' => NULL,
                'autocrm_sms_content' => NULL,
                'autocrm_push_subject' => 'Your Order is Ready',
                'autocrm_push_content' => '%name%, your order at %outlet_name% is ready. Please come to the outlet to take your order',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Your Order is Ready',
                'autocrm_inbox_content' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            11 => 
            array (
                'id_autocrm' => 12,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Order Accepted',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => NULL,
                'autocrm_email_content' => NULL,
                'autocrm_sms_content' => NULL,
                'autocrm_push_subject' => 'Your Order has been accepted',
                'autocrm_push_content' => '%name%, your order at %outlet_name% has been accpeted. We will immediately process your order',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Your Order has been accepted',
                'autocrm_inbox_content' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            12 => 
            array (
                'id_autocrm' => 13,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Order Taken',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => NULL,
                'autocrm_email_content' => NULL,
                'autocrm_sms_content' => NULL,
                'autocrm_push_subject' => 'Your Order has been taken',
                'autocrm_push_content' => '%name%, your order at %outlet_name% has been taken. Thank you for buying at our outlet',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Your Order has been taken',
                'autocrm_inbox_content' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            13 => 
            array (
                'id_autocrm' => 14,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Order Reject',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '1',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => NULL,
                'autocrm_email_content' => NULL,
                'autocrm_sms_content' => NULL,
                'autocrm_push_subject' => 'Your Order has been rejected',
                'autocrm_push_content' => 'hai %name%, Sorry your order at %outlet_name% has been rejected. ',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'Sorry, Your Order has been rejected',
                'autocrm_inbox_content' => NULL,
                'autocrm_push_clickto' => 'transaction',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            14 => 
            array (
                'id_autocrm' => 15,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Pin Forgot',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => 'Hello, Bro Your PIN is %pin%',
                'autocrm_email_content' => 'Hello, Your PIN is %pin%',
                'autocrm_sms_content' => 'Hello, Your PIN is %pin%',
                'autocrm_push_subject' => NULL,
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => NULL,
                'autocrm_inbox_content' => NULL,
                'autocrm_forward_email' => NULL,
                'autocrm_forward_email_subject' => NULL,
                'autocrm_forward_email_content' => NULL,
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-05-03 15:02:05',
            ),
            15 => 
            array (
                'id_autocrm' => 16,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Claim Deals Success',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => '',
                'autocrm_email_content' => '',
                'autocrm_sms_content' => '',
                'autocrm_push_subject' => '',
                'autocrm_push_content' => '',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => '',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => '',
                'autocrm_inbox_content' => '',
                'autocrm_forward_email' => '',
                'autocrm_forward_email_subject' => '',
                'autocrm_forward_email_content' => '',
                'custom_text_replace'=>'%claimed_at%;%deals_title%;%deals_voucher_price_point%;',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            16 => 
            array (
                'id_autocrm' => 17,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Redeem Voucher Success',
                'autocrm_email_toogle' => '1',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '1',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '1',
                'autocrm_email_subject' => 'New Deals',
                'autocrm_email_content' => 'New Deals',
                'autocrm_sms_content' => 'New Deals',
                'autocrm_push_subject' => 'New Deals',
                'autocrm_push_content' => NULL,
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => NULL,
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => 'New Deals',
                'autocrm_inbox_content' => 'New Deals',
                'autocrm_forward_email' => 'wizemakers@gmail.com;ivankp@technopartner.id',
                'autocrm_forward_email_subject' => 'New Deals',
                'autocrm_forward_email_content' => 'New Deals',
                'custom_text_replace'=>'%redeemed_at%;%voucher_code%;%outlet_name%;%outlet_code%;',
                'created_at' => '2018-03-12 13:53:17',
                'updated_at' => '2018-04-23 06:42:39',
            ),
            17 => 
            array (
                'id_autocrm' => 18,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Transaction Point Achievement',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => '',
                'autocrm_email_content' => '',
                'autocrm_sms_content' => '',
                'autocrm_push_subject' => '',
                'autocrm_push_content' => '',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => '',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => '',
                'autocrm_inbox_content' => '',
                'autocrm_forward_email' => '',
                'autocrm_forward_email_subject' => '',
                'autocrm_forward_email_content' => '',
                'custom_text_replace'=>'%receipt_number%;%received_point%;%outlet_name%;%transaction_date%;',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            18 => 
            array (
                'id_autocrm' => 19,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Transaction Failed Point Refund',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => '',
                'autocrm_email_content' => '',
                'autocrm_sms_content' => '',
                'autocrm_push_subject' => '',
                'autocrm_push_content' => '',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => '',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => '',
                'autocrm_inbox_content' => '',
                'autocrm_forward_email' => '',
                'autocrm_forward_email_subject' => '',
                'autocrm_forward_email_content' => '',
                'custom_text_replace'=>'%receipt_number%;%received_point%;%outlet_name%;%transaction_date%;',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            19 => 
            array (
                'id_autocrm' => 20,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Rejected Order Point Refund',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => '',
                'autocrm_email_content' => '',
                'autocrm_sms_content' => '',
                'autocrm_push_subject' => '',
                'autocrm_push_content' => '',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => '',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => '',
                'autocrm_inbox_content' => '',
                'autocrm_forward_email' => '',
                'autocrm_forward_email_subject' => '',
                'autocrm_forward_email_content' => '',
                'custom_text_replace'=>'%receipt_number%;%received_point%;%outlet_name%;%transaction_date%;',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            20 => 
            array (
                'id_autocrm' => 21,
                'autocrm_type' => 'Response',
                'autocrm_trigger' => 'Daily',
                'autocrm_cron_reference' => NULL,
                'autocrm_title' => 'Complete User Profile Point Bonus',
                'autocrm_email_toogle' => '0',
                'autocrm_sms_toogle' => '0',
                'autocrm_push_toogle' => '0',
                'autocrm_inbox_toogle' => '0',
                'autocrm_forward_toogle' => '0',
                'autocrm_email_subject' => '',
                'autocrm_email_content' => '',
                'autocrm_sms_content' => '',
                'autocrm_push_subject' => '',
                'autocrm_push_content' => '',
                'autocrm_push_image' => NULL,
                'autocrm_push_clickto' => '',
                'autocrm_push_link' => NULL,
                'autocrm_push_id_reference' => NULL,
                'autocrm_inbox_subject' => '',
                'autocrm_inbox_content' => '',
                'autocrm_forward_email' => '',
                'autocrm_forward_email_subject' => '',
                'autocrm_forward_email_content' => '',
                'custom_text_replace'=>'%received_point%;',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
        ));
        
        
    }
}