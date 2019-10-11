<?php

use Illuminate\Database\Seeder;

class AutocrmsTableAddClaimDeals extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('autocrms')->insert(array (
            0 => 
            array (
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
            1 => 
            array (
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
            )
        ));
    }
}
