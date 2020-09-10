<?php

use Illuminate\Database\Seeder;

class ConfigsTableSeeder extends Seeder
{
    public function run()
    {
        \DB::table('configs')->delete();

        \DB::table('configs')->insert(array (
            0 =>
            array (
                'id_config' => 1,
                'config_name' => 'sync raptor',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            1 =>
            array (
                'id_config' => 2,
                'config_name' => 'outlet import excel',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            2 =>
            array (
                'id_config' => 3,
                'config_name' => 'outlet export excel',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            3 =>
            array (
                'id_config' => 4,
                'config_name' => 'outlet holiday',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            4 =>
            array (
                'id_config' => 5,
                'config_name' => 'admin outlet',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            5 =>
            array (
                'id_config' => 6,
                'config_name' => 'admin outlet pickup order',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            6 =>
            array (
                'id_config' => 7,
                'config_name' => 'admin outlet delivery order',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            7 =>
            array (
                'id_config' => 8,
                'config_name' => 'admin outlet finance',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            8 =>
            array (
                'id_config' => 9,
                'config_name' => 'admin outlet enquiry',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            9 =>
            array (
                'id_config' => 10,
                'config_name' => 'product import excel',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            10 =>
            array (
                'id_config' => 11,
                'config_name' => 'product export excel',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            11 =>
            array (
                'id_config' => 12,
                'config_name' => 'pickup order',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            12 =>
            array (
                'id_config' => 13,
                'config_name' => 'delivery order',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            13 =>
            array (
                'id_config' => 14,
                'config_name' => 'internal courier',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            14 =>
            array (
                'id_config' => 15,
                'config_name' => 'online order',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            15 =>
            array (
                'id_config' => 16,
                'config_name' => 'automatic payment',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            16 =>
            array (
                'id_config' => 17,
                'config_name' => 'manual payment',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            17 =>
            array (
                'id_config' => 18,
                'config_name' => 'point',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            18 =>
            array (
                'id_config' => 19,
                'config_name' => 'balance',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            19 =>
            array (
                'id_config' => 20,
                'config_name' => 'membership',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            20 =>
            array (
                'id_config' => 21,
                'config_name' => 'membership benefit point',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            21 =>
            array (
                'id_config' => 22,
                'config_name' => 'membership benefit cashback',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            22 =>
            array (
                'id_config' => 23,
                'config_name' => 'membership benefit discount',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            23 =>
            array (
                'id_config' => 24,
                'config_name' => 'membership benefit promo id',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            24 =>
            array (
                'id_config' => 25,
                'config_name' => 'deals',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            25 =>
            array (
                'id_config' => 26,
                'config_name' => 'hidden deals',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            26 =>
            array (
                'id_config' => 27,
                'config_name' => 'deals by money',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            27 =>
            array (
                'id_config' => 28,
                'config_name' => 'deals by point',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            28 =>
            array (
                'id_config' => 29,
                'config_name' => 'deals free',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            29 =>
            array (
                'id_config' => 30,
                'config_name' => 'greetings',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            30 =>
            array (
                'id_config' => 31,
                'config_name' => 'greetings text',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            31 =>
            array (
                'id_config' => 32,
                'config_name' => 'greetings background',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            32 =>
            array (
                'id_config' => 33,
                'config_name' => 'advert',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            33 =>
            array (
                'id_config' => 34,
                'config_name' => 'news',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            34 =>
            array (
                'id_config' => 35,
                'config_name' => 'crm',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            35 =>
            array (
                'id_config' => 36,
                'config_name' => 'crm push notification',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            36 =>
            array (
                'id_config' => 37,
                'config_name' => 'crm inbox',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            37 =>
            array (
                'id_config' => 38,
                'config_name' => 'crm email',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            38 =>
            array (
                'id_config' => 39,
                'config_name' => 'crm sms',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            39 =>
            array (
                'id_config' => 40,
                'config_name' => 'auto response',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            40 =>
            array (
                'id_config' => 41,
                'config_name' => 'auto response pin sent',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            41 =>
            array (
                'id_config' => 42,
                'config_name' => 'auto response pin verified',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            42 =>
            array (
                'id_config' => 43,
                'config_name' => 'auto response pin changed',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            43 =>
            array (
                'id_config' => 44,
                'config_name' => 'auto response login success',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            44 =>
            array (
                'id_config' => 45,
                'config_name' => 'auto response login failed',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            45 =>
            array (
                'id_config' => 46,
                'config_name' => 'auto response enquiry question',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            46 =>
            array (
                'id_config' => 47,
                'config_name' => 'auto response enquiry partnership',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            47 =>
            array (
                'id_config' => 48,
                'config_name' => 'auto response enquiry complaint',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            48 =>
            array (
                'id_config' => 49,
                'config_name' => 'auto response deals',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            49 =>
            array (
                'id_config' => 50,
                'config_name' => 'campaign',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            50 =>
            array (
                'id_config' => 51,
                'config_name' => 'campaign email',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            51 =>
            array (
                'id_config' => 52,
                'config_name' => 'campaign sms',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            52 =>
            array (
                'id_config' => 53,
                'config_name' => 'campaign push notif',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            53 =>
            array (
                'id_config' => 54,
                'config_name' => 'campaign inbox',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            54 =>
            array (
                'id_config' => 55,
                'config_name' => 'auto crm',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            55 =>
            array (
                'id_config' => 56,
                'config_name' => 'enquiry',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            56 =>
            array (
                'id_config' => 57,
                'config_name' => 'reply enquiry',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            57 =>
            array (
                'id_config' => 58,
                'config_name' => 'enquiry question',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            58 =>
            array (
                'id_config' => 59,
                'config_name' => 'enquiry partnership',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            59 =>
            array (
                'id_config' => 60,
                'config_name' => 'enquiry complaint',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            60 =>
            array (
                'id_config' => 61,
                'config_name' => 'report transaction daily',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            61 =>
            array (
                'id_config' => 62,
                'config_name' => 'report transaction weekly',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            62 =>
            array (
                'id_config' => 63,
                'config_name' => 'report transaction monthly',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            63 =>
            array (
                'id_config' => 64,
                'config_name' => 'report transaction yearly',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            64 =>
            array (
                'id_config' => 65,
                'config_name' => 'product by recurring',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            65 =>
            array (
                'id_config' => 66,
                'config_name' => 'product by quantity',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            66 =>
            array (
                'id_config' => 67,
                'config_name' => 'outlet by nominal transaction',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            67 =>
            array (
                'id_config' => 68,
                'config_name' => 'outlet by total transaction',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            68 =>
            array (
                'id_config' => 69,
                'config_name' => 'customer by total transaction',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            69 =>
            array (
                'id_config' => 70,
                'config_name' => 'customer by nominal transaction',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            70 =>
            array (
                'id_config' => 71,
                'config_name' => 'customer by point',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            71 =>
            array (
                'id_config' => 72,
                'config_name' => 'promotion',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            72 =>
            array (
                'id_config' => 73,
                'config_name' => 'reward',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            73 =>
            array (
                'id_config' => 74,
                'config_name' => 'crm whatsapp',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            74 =>
            array (
                'id_config' => 75,
                'config_name' => 'campaign whatsapp',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            75 =>
            array (
                'id_config' => 76,
                'config_name' => 'spin the wheel',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            76 =>
            array (
                'id_config' => 77,
                'config_name' => 'point reset',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            77 =>
            array (
                'id_config' => 78,
                'config_name' => 'balance reset',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            78 =>
            array (
                'id_config' => 79,
                'config_name' => 'free delivery',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            79 =>
            array (
                'id_config' => 80,
                'config_name' => 'GO-SEND',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            80 =>
            array (
                'id_config' => 81,
                'config_name' => 'retain membership',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            81 =>
            array (
                'id_config' => 82,
                'config_name' => 'POS sync Outlet',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            82 =>
            array (
                'id_config' => 83,
                'config_name' => 'auto response pin forgot',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            83 =>
            array (
                'id_config' => 84,
                'config_name' => 'subscription voucher',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            84 =>
            array (
                'id_config' => 85,
                'config_name' => 'subscription voucher by money',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            85 =>
            array (
                'id_config' => 86,
                'config_name' => 'subscription voucher by point',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            86 =>
            array (
                'id_config' => 87,
                'config_name' => 'subscription voucher free',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            87 =>
                array (
                    'id_config' => 88,
                    'config_name' => 'icon main menu',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            88 =>
                array (
                    'id_config' => 89,
                    'config_name' => 'icon other menu',
                    'description' => '',
                    'is_active' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            89 =>
                array (
                    'id_config' => 90,
                    'config_name' => 'user feedback',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            90 =>
                array (
                    'id_config' => 91,
                    'config_name' => 'product modifier',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            91 =>
            array (
                'id_config' => 92,
                'config_name' => 'advance order',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            92 =>
                array (
                    'id_config' => 93,
                    'config_name' => 'promo campaign',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            93 =>
                array (
                    'id_config' => 94,
                    'config_name' => 'phone format setting',
                    'description' => '',
                    'is_active' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            94 =>
                array (
                    'id_config' => 95,
                    'config_name' => 'use brand',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            95 =>
                array (
                    'id_config' => 96,
                    'config_name' => 'delivery services',
                    'description' => '',
                    'is_active' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
			96 =>
                array (
                    'id_config' => 97,
                    'config_name' => 'deals offline',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            97 =>
                array (
                    'id_config' => 98,
                    'config_name' => 'deals online',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            98 =>
            array (
                'id_config' => 99,
                'config_name' => 'achievement',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            99 =>
            array (
                'id_config' => 100,
                'config_name' => 'quest',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            100 =>
            array (
                'id_config' => 101,
                'config_name' => 'admin outlet apps',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            101 =>
            array (
                'id_config' => 102,
                'config_name' => 'voucher online get point',
                'description' => '',
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            102 =>
            array (
                'id_config' => 103,
                'config_name' => 'voucher offline get point',
                'description' => '',
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            103 =>
            array (
                'id_config' => 104,
                'config_name' => 'promo code get point',
                'description' => '',
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            104 =>
            array (
                'id_config' => 105,
                'config_name' => 'deals second title',
                'description' => '',
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            105 =>
                array (
                    'id_config' => 106,
                    'config_name' => 'auto response email verified',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            106 =>
                array (
                    'id_config' => 107,
                    'config_name' => 'custom form news',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            107 =>
                array (
                    'id_config' => 108,
                    'config_name' => 'intro',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            108 =>
                array (
                    'id_config' => 109,
                    'config_name' => 'credit card multi payment',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            109 =>
                array (
                    'id_config' => 110,
                    'config_name' => 'refund midtrans',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            110 =>
                array (
                    'id_config' => 111,
                    'config_name' => 'refund ovo',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            111 =>
            array (
                'id_config' => 112,
                'config_name' => 'subscription get point',
                'description' => '',
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            112 =>
            array (
                'id_config' => 113,
                'config_name' => 'auto response subscription',
                'description' => '',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            113 =>
                array (
                    'id_config' => 114,
                    'config_name' => 'fraud use queue',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            114 =>
                array (
                    'id_config' => 115,
                    'config_name' => 'referral',
                    'description' => '',
                    'is_active' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            115 =>
                array (
                    'id_config' => 116,
                    'config_name' => 'offline payment method',
                    'description' => '',
                    'is_active' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            116 =>
                array (
                    'id_config' => 117,
                    'config_name' => 'banner daily time limit',
                    'description' => '',
                    'is_active' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            117 =>
                array (
                    'id_config' => 118,
                    'config_name' => 'show or hide info calculation disburse',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            109 =>
                array (
                    'id_config' => 110,
                    'config_name' => 'shopeepay',
                    'description' => '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
        ));
    }
}
