<?php

use Illuminate\Database\Seeder;

class FeaturesTableSeeder extends Seeder
{
    public function run()
    {


        \DB::table('features')->delete();

        \DB::table('features')->insert(array(
            0 =>
            array(
                'id_feature' => 1,
                'feature_type' => 'Report',
                'feature_module' => 'Dashboard',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            1 =>
            array(
                'id_feature' => 2,
                'feature_type' => 'List',
                'feature_module' => 'Users',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            2 =>
            array(
                'id_feature' => 3,
                'feature_type' => 'Detail',
                'feature_module' => 'Users',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            3 =>
            array(
                'id_feature' => 4,
                'feature_type' => 'Create',
                'feature_module' => 'Users',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            4 =>
            array(
                'id_feature' => 5,
                'feature_type' => 'Update',
                'feature_module' => 'Users',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            5 =>
            array(
                'id_feature' => 6,
                'feature_type' => 'Delete',
                'feature_module' => 'Users',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            6 =>
            array(
                'id_feature' => 7,
                'feature_type' => 'List',
                'feature_module' => 'Log Activity',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            7 =>
            array(
                'id_feature' => 8,
                'feature_type' => 'Detail',
                'feature_module' => 'Log Activity',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            8 =>
            array(
                'id_feature' => 9,
                'feature_type' => 'List',
                'feature_module' => 'Admin Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            9 =>
            array(
                'id_feature' => 10,
                'feature_type' => 'List',
                'feature_module' => 'Membership',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            10 =>
            array(
                'id_feature' => 11,
                'feature_type' => 'Detail',
                'feature_module' => 'Membership',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            11 =>
            array(
                'id_feature' => 12,
                'feature_type' => 'Create',
                'feature_module' => 'Membership',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            12 =>
            array(
                'id_feature' => 13,
                'feature_type' => 'Update',
                'feature_module' => 'Membership',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            13 =>
            array(
                'id_feature' => 14,
                'feature_type' => 'Delete',
                'feature_module' => 'Membership',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            14 =>
            array(
                'id_feature' => 15,
                'feature_type' => 'List',
                'feature_module' => 'Greeting & Background',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            15 =>
            array(
                'id_feature' => 16,
                'feature_type' => 'Create',
                'feature_module' => 'Greeting & Background',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            16 =>
            array(
                'id_feature' => 17,
                'feature_type' => 'Update',
                'feature_module' => 'Greeting & Background',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            17 =>
            array(
                'id_feature' => 18,
                'feature_type' => 'Delete',
                'feature_module' => 'Greeting & Background',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            18 =>
            array(
                'id_feature' => 19,
                'feature_type' => 'List',
                'feature_module' => 'News',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            19 =>
            array(
                'id_feature' => 20,
                'feature_type' => 'Detail',
                'feature_module' => 'News',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            20 =>
            array(
                'id_feature' => 21,
                'feature_type' => 'Create',
                'feature_module' => 'News',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            21 =>
            array(
                'id_feature' => 22,
                'feature_type' => 'Update',
                'feature_module' => 'News',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            22 =>
            array(
                'id_feature' => 23,
                'feature_type' => 'Delete',
                'feature_module' => 'News',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            23 =>
            array(
                'id_feature' => 24,
                'feature_type' => 'List',
                'feature_module' => 'Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            24 =>
            array(
                'id_feature' => 25,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            25 =>
            array(
                'id_feature' => 26,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            26 =>
            array(
                'id_feature' => 27,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            27 =>
            array(
                'id_feature' => 28,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            28 =>
            array(
                'id_feature' => 29,
                'feature_type' => 'List',
                'feature_module' => 'Outlet Photo',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            29 =>
            array(
                'id_feature' => 30,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet Photo',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            30 =>
            array(
                'id_feature' => 31,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet Photo',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            31 =>
            array(
                'id_feature' => 32,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Import',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            32 =>
            array(
                'id_feature' => 33,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet Export',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            33 =>
            array(
                'id_feature' => 34,
                'feature_type' => 'List',
                'feature_module' => 'Outlet Holiday',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            34 =>
            array(
                'id_feature' => 35,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet Holiday',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            35 =>
            array(
                'id_feature' => 36,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet Holiday',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            36 =>
            array(
                'id_feature' => 37,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Holiday',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            37 =>
            array(
                'id_feature' => 38,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet Holiday',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            38 =>
            array(
                'id_feature' => 39,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet Admin',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            39 =>
            array(
                'id_feature' => 40,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet Admin',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            40 =>
            array(
                'id_feature' => 41,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Admin',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            41 =>
            array(
                'id_feature' => 42,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet Admin',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            42 =>
            array(
                'id_feature' => 43,
                'feature_type' => 'List',
                'feature_module' => 'Product Category',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            43 =>
            array(
                'id_feature' => 44,
                'feature_type' => 'Detail',
                'feature_module' => 'Product Category',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            44 =>
            array(
                'id_feature' => 45,
                'feature_type' => 'Create',
                'feature_module' => 'Product Category',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            45 =>
            array(
                'id_feature' => 46,
                'feature_type' => 'Update',
                'feature_module' => 'Product Category',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            46 =>
            array(
                'id_feature' => 47,
                'feature_type' => 'Delete',
                'feature_module' => 'Product Category',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            47 =>
            array(
                'id_feature' => 48,
                'feature_type' => 'List',
                'feature_module' => 'Product',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            48 =>
            array(
                'id_feature' => 49,
                'feature_type' => 'Detail',
                'feature_module' => 'Product',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            49 =>
            array(
                'id_feature' => 50,
                'feature_type' => 'Create',
                'feature_module' => 'Product',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            50 =>
            array(
                'id_feature' => 51,
                'feature_type' => 'Update',
                'feature_module' => 'Product',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            51 =>
            array(
                'id_feature' => 52,
                'feature_type' => 'Delete',
                'feature_module' => 'Product',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            52 =>
            array(
                'id_feature' => 53,
                'feature_type' => 'List',
                'feature_module' => 'Product Photo',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            53 =>
            array(
                'id_feature' => 54,
                'feature_type' => 'Create',
                'feature_module' => 'Product Photo',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            54 =>
            array(
                'id_feature' => 55,
                'feature_type' => 'Delete',
                'feature_module' => 'Product Photo',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            55 =>
            array(
                'id_feature' => 56,
                'feature_type' => 'Update',
                'feature_module' => 'Product Import',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            56 =>
            array(
                'id_feature' => 57,
                'feature_type' => 'Detail',
                'feature_module' => 'Product Export',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            57 =>
            array(
                'id_feature' => 58,
                'feature_type' => 'Update',
                'feature_module' => 'Grand Total Calculation Rule',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            58 =>
            array(
                'id_feature' => 59,
                'feature_type' => 'Update',
                'feature_module' => 'Point Acquisition Setting',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            59 =>
            array(
                'id_feature' => 60,
                'feature_type' => 'Update',
                'feature_module' => 'Cashback Acquisition Setting',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            60 =>
            array(
                'id_feature' => 61,
                'feature_type' => 'Update',
                'feature_module' => 'Delivery Price Setting',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            61 =>
            array(
                'id_feature' => 62,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Product Price Setting',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            62 =>
            array(
                'id_feature' => 63,
                'feature_type' => 'Update',
                'feature_module' => 'Internal Courier Setting',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            63 =>
            array(
                'id_feature' => 64,
                'feature_type' => 'List',
                'feature_module' => 'Manual Payment',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            64 =>
            array(
                'id_feature' => 65,
                'feature_type' => 'Detail',
                'feature_module' => 'Manual Payment',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            65 =>
            array(
                'id_feature' => 66,
                'feature_type' => 'Create',
                'feature_module' => 'Manual Payment',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            66 =>
            array(
                'id_feature' => 67,
                'feature_type' => 'Update',
                'feature_module' => 'Manual Payment',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            67 =>
            array(
                'id_feature' => 68,
                'feature_type' => 'Delete',
                'feature_module' => 'Manual Payment',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            68 =>
            array(
                'id_feature' => 69,
                'feature_type' => 'List',
                'feature_module' => 'Transaction',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            69 =>
            array(
                'id_feature' => 70,
                'feature_type' => 'Detail',
                'feature_module' => 'Transaction',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            70 =>
            array(
                'id_feature' => 71,
                'feature_type' => 'List',
                'feature_module' => 'Point Log History',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            71 =>
            array(
                'id_feature' => 72,
                'feature_type' => 'List',
                'feature_module' => 'Deals',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            72 =>
            array(
                'id_feature' => 73,
                'feature_type' => 'Detail',
                'feature_module' => 'Deals',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            73 =>
            array(
                'id_feature' => 74,
                'feature_type' => 'Create',
                'feature_module' => 'Deals',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            74 =>
            array(
                'id_feature' => 75,
                'feature_type' => 'Update',
                'feature_module' => 'Deals',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            75 =>
            array(
                'id_feature' => 76,
                'feature_type' => 'Delete',
                'feature_module' => 'Deals',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            76 =>
            array(
                'id_feature' => 77,
                'feature_type' => 'List',
                'feature_module' => 'Deals Hidden',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            77 =>
            array(
                'id_feature' => 78,
                'feature_type' => 'Detail',
                'feature_module' => 'Deals Hidden',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            78 =>
            array(
                'id_feature' => 79,
                'feature_type' => 'Create',
                'feature_module' => 'Deals Hidden',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            79 =>
            array(
                'id_feature' => 80,
                'feature_type' => 'Update',
                'feature_module' => 'Deals Hidden',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            80 =>
            array(
                'id_feature' => 81,
                'feature_type' => 'Delete',
                'feature_module' => 'Deals Hidden',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            81 =>
            array(
                'id_feature' => 82,
                'feature_type' => 'Update',
                'feature_module' => 'Text Replace',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            82 =>
            array(
                'id_feature' => 83,
                'feature_type' => 'List',
                'feature_module' => 'Enquiries',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            83 =>
            array(
                'id_feature' => 84,
                'feature_type' => 'Detail',
                'feature_module' => 'Enquiries',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            84 =>
            array(
                'id_feature' => 85,
                'feature_type' => 'Update',
                'feature_module' => 'About Us',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            85 =>
            array(
                'id_feature' => 86,
                'feature_type' => 'Update',
                'feature_module' => 'Terms Of Services',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            86 =>
            array(
                'id_feature' => 87,
                'feature_type' => 'Update',
                'feature_module' => 'Contact Us',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            87 =>
            array(
                'id_feature' => 88,
                'feature_type' => 'List',
                'feature_module' => 'Frequently Asked Question',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            88 =>
            array(
                'id_feature' => 89,
                'feature_type' => 'Create',
                'feature_module' => 'Frequently Asked Question',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            89 =>
            array(
                'id_feature' => 90,
                'feature_type' => 'Update',
                'feature_module' => 'Frequently Asked Question',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            90 =>
            array(
                'id_feature' => 91,
                'feature_type' => 'Delete',
                'feature_module' => 'Frequently Asked Question',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            91 =>
            array(
                'id_feature' => 92,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM User',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            92 =>
            array(
                'id_feature' => 93,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Transaction',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            93 =>
            array(
                'id_feature' => 94,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Enquiry',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            94 =>
            array(
                'id_feature' => 95,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Deals',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            95 =>
            array(
                'id_feature' => 96,
                'feature_type' => 'Update',
                'feature_module' => 'Text Replaces',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            96 =>
            array(
                'id_feature' => 97,
                'feature_type' => 'Update',
                'feature_module' => 'Email Header & Footer',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            97 =>
            array(
                'id_feature' => 98,
                'feature_type' => 'List',
                'feature_module' => 'Campaign',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            98 =>
            array(
                'id_feature' => 99,
                'feature_type' => 'Detail',
                'feature_module' => 'Campaign',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            99 =>
            array(
                'id_feature' => 100,
                'feature_type' => 'Create',
                'feature_module' => 'Campaign',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            100 =>
            array(
                'id_feature' => 101,
                'feature_type' => 'Update',
                'feature_module' => 'Campaign',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            101 =>
            array(
                'id_feature' => 102,
                'feature_type' => 'Delete',
                'feature_module' => 'Campaign',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            102 =>
            array(
                'id_feature' => 103,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Email Queue',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            103 =>
            array(
                'id_feature' => 104,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Email Sent',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            104 =>
            array(
                'id_feature' => 105,
                'feature_type' => 'List',
                'feature_module' => 'Campaign SMS Queue',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            105 =>
            array(
                'id_feature' => 106,
                'feature_type' => 'List',
                'feature_module' => 'Campaign SMS Sent',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            106 =>
            array(
                'id_feature' => 107,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Push Queue',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            107 =>
            array(
                'id_feature' => 108,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Push Sent',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            108 =>
            array(
                'id_feature' => 109,
                'feature_type' => 'List',
                'feature_module' => 'Promotion',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            109 =>
            array(
                'id_feature' => 110,
                'feature_type' => 'Detail',
                'feature_module' => 'Promotion',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            110 =>
            array(
                'id_feature' => 111,
                'feature_type' => 'Create',
                'feature_module' => 'Promotion',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            111 =>
            array(
                'id_feature' => 112,
                'feature_type' => 'Update',
                'feature_module' => 'Promotion',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            112 =>
            array(
                'id_feature' => 113,
                'feature_type' => 'Delete',
                'feature_module' => 'Promotion',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            113 =>
            array(
                'id_feature' => 114,
                'feature_type' => 'List',
                'feature_module' => 'Inbox Global',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            114 =>
            array(
                'id_feature' => 115,
                'feature_type' => 'Detail',
                'feature_module' => 'Inbox Global',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            115 =>
            array(
                'id_feature' => 116,
                'feature_type' => 'Create',
                'feature_module' => 'Inbox Global',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            116 =>
            array(
                'id_feature' => 117,
                'feature_type' => 'Update',
                'feature_module' => 'Inbox Global',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            117 =>
            array(
                'id_feature' => 118,
                'feature_type' => 'Delete',
                'feature_module' => 'Inbox Global',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            118 =>
            array(
                'id_feature' => 119,
                'feature_type' => 'List',
                'feature_module' => 'Auto CRM',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            119 =>
            array(
                'id_feature' => 120,
                'feature_type' => 'Detail',
                'feature_module' => 'Auto CRM',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            120 =>
            array(
                'id_feature' => 121,
                'feature_type' => 'Create',
                'feature_module' => 'Auto CRM',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            121 =>
            array(
                'id_feature' => 122,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            122 =>
            array(
                'id_feature' => 123,
                'feature_type' => 'Delete',
                'feature_module' => 'Auto CRM',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            123 =>
            array(
                'id_feature' => 124,
                'feature_type' => 'Update',
                'feature_module' => 'Advertisement',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            124 =>
            array(
                'id_feature' => 125,
                'feature_type' => 'Report',
                'feature_module' => 'Report Global',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            125 =>
            array(
                'id_feature' => 126,
                'feature_type' => 'Report',
                'feature_module' => 'Report Customer',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            126 =>
            array(
                'id_feature' => 127,
                'feature_type' => 'Report',
                'feature_module' => 'Report Product',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            127 =>
            array(
                'id_feature' => 128,
                'feature_type' => 'Report',
                'feature_module' => 'Report Outlet',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            128 =>
            array(
                'id_feature' => 129,
                'feature_type' => 'Report',
                'feature_module' => 'Magic Report',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            129 =>
            array(
                'id_feature' => 130,
                'feature_type' => 'List',
                'feature_module' => 'Reward',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            130 =>
            array(
                'id_feature' => 131,
                'feature_type' => 'Detail',
                'feature_module' => 'Reward',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            131 =>
            array(
                'id_feature' => 132,
                'feature_type' => 'Create',
                'feature_module' => 'Reward',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            132 =>
            array(
                'id_feature' => 133,
                'feature_type' => 'Update',
                'feature_module' => 'Reward',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            133 =>
            array(
                'id_feature' => 134,
                'feature_type' => 'Delete',
                'feature_module' => 'Reward',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            134 =>
            array(
                'id_feature' => 135,
                'feature_type' => 'Create',
                'feature_module' => 'Spin The Wheel',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            135 =>
            array(
                'id_feature' => 136,
                'feature_type' => 'Update',
                'feature_module' => 'Spin The Wheel',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            136 =>
            array(
                'id_feature' => 137,
                'feature_type' => 'Delete',
                'feature_module' => 'Spin The Wheel',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            137 =>
            array(
                'id_feature' => 138,
                'feature_type' => 'Update',
                'feature_module' => 'Spin The Wheel Setting',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            138 =>
            array(
                'id_feature' => 139,
                'feature_type' => 'List',
                'feature_module' => 'Deals Subscription',
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            139 =>
            array(
                'id_feature' => 140,
                'feature_type' => 'Detail',
                'feature_module' => 'Deals Subscription',
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            140 =>
            array(
                'id_feature' => 141,
                'feature_type' => 'Create',
                'feature_module' => 'Deals Subscription',
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            141 =>
            array(
                'id_feature' => 142,
                'feature_type' => 'Update',
                'feature_module' => 'Deals Subscription',
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            142 =>
            array(
                'id_feature' => 143,
                'feature_type' => 'Delete',
                'feature_module' => 'Deals Subscription',
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            143 =>
            array(
                'id_feature' => 144,
                'feature_type' => 'List',
                'feature_module' => 'Banner',
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            144 =>
            array(
                'id_feature' => 145,
                'feature_type' => 'Create',
                'feature_module' => 'Banner',
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            145 =>
            array(
                'id_feature' => 146,
                'feature_type' => 'Update',
                'feature_module' => 'Banner',
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            146 =>
            array(
                'id_feature' => 147,
                'feature_type' => 'Delete',
                'feature_module' => 'Banner',
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            147 =>
            array(
                'id_feature' => 148,
                'feature_type' => 'Update',
                'feature_module' => 'User Profile Completing',
                'created_at' => '2018-12-17 16:20:00',
                'updated_at' => '2018-12-17 16:20:00',
            ),
            148 =>
            array(
                'id_feature' => 149,
                'feature_type' => 'List',
                'feature_module' => 'Custom Page',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            149 =>
            array(
                'id_feature' => 150,
                'feature_type' => 'Create',
                'feature_module' => 'Custom Page',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            150 =>
            array(
                'id_feature' => 151,
                'feature_type' => 'Update',
                'feature_module' => 'Custom Page',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            151 =>
            array(
                'id_feature' => 152,
                'feature_type' => 'Delete',
                'feature_module' => 'Custom Page',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            152 =>
            array(
                'id_feature' => 153,
                'feature_type' => 'Detail',
                'feature_module' => 'Custom Page',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            153 =>
            array(
                'id_feature' => 154,
                'feature_type' => 'Create',
                'feature_module' => 'Delivery Service',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            154 =>
            array(
                'id_feature' => 155,
                'feature_type' => 'List',
                'feature_module' => 'Brand',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            155 =>
            array(
                'id_feature' => 156,
                'feature_type' => 'Create',
                'feature_module' => 'Brand',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            156 =>
            array(
                'id_feature' => 157,
                'feature_type' => 'Update',
                'feature_module' => 'Brand',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            157 =>
            array(
                'id_feature' => 158,
                'feature_type' => 'Delete',
                'feature_module' => 'Brand',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            158 =>
            array(
                'id_feature' => 159,
                'feature_type' => 'Detail',
                'feature_module' => 'Brand',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            159 =>
            array(
                'id_feature' => 160,
                'feature_type' => 'List',
                'feature_module' => 'Text Menu',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            160 =>
            array(
                'id_feature' => 161,
                'feature_type' => 'Update',
                'feature_module' => 'Text Menu',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            161 =>
            array(
                'id_feature' => 162,
                'feature_type' => 'List',
                'feature_module' => 'Confirmation Messages',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            162 =>
            array(
                'id_feature' => 163,
                'feature_type' => 'Update',
                'feature_module' => 'Confirmation Messages',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            163 =>
            array(
                'id_feature' => 164,
                'feature_type' => 'List',
                'feature_module' => 'News Category',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            164 =>
            array(
                'id_feature' => 165,
                'feature_type' => 'Create',
                'feature_module' => 'News Category',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            165 =>
            array(
                'id_feature' => 166,
                'feature_type' => 'Update',
                'feature_module' => 'News Category',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            166 =>
            array(
                'id_feature' => 167,
                'feature_type' => 'Delete',
                'feature_module' => 'News Category',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            167 =>
            array(
                'id_feature' => 168,
                'feature_type' => 'List',
                'feature_module' => 'Intro',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            168 =>
            array(
                'id_feature' => 169,
                'feature_type' => 'Create',
                'feature_module' => 'Intro',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            169 =>
            array(
                'id_feature' => 170,
                'feature_type' => 'Update',
                'feature_module' => 'Intro',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            170 =>
            array(
                'id_feature' => 171,
                'feature_type' => 'Delete',
                'feature_module' => 'Intro',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            171 =>
            array(
                'id_feature' => 172,
                'feature_type' => 'Create',
                'feature_module' => 'Subscription',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            172 =>
            array(
                'id_feature' => 173,
                'feature_type' => 'List',
                'feature_module' => 'Subscription',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            173 =>
            array(
                'id_feature' => 174,
                'feature_type' => 'Detail',
                'feature_module' => 'Subscription',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            174 =>
            array(
                'id_feature' => 175,
                'feature_type' => 'Update',
                'feature_module' => 'Subscription',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            175 =>
            array(
                'id_feature' => 176,
                'feature_type' => 'Delete',
                'feature_module' => 'Subscription',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            176 =>
            array(
                'id_feature' => 177,
                'feature_type' => 'Report',
                'feature_module' => 'Subscription',
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            )
        ));
    }
}
