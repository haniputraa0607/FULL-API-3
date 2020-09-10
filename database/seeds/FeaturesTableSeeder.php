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
                'show_hide' => 1,
                'order' => 1,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            1 =>
            array(
                'id_feature' => 2,
                'feature_type' => 'List',
                'feature_module' => 'Users',
                'show_hide' => 1,
                'order' => 2,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            2 =>
            array(
                'id_feature' => 3,
                'feature_type' => 'Detail',
                'feature_module' => 'Users',
                'show_hide' => 1,
                'order' => 2,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            3 =>
            array(
                'id_feature' => 4,
                'feature_type' => 'Create',
                'feature_module' => 'Users',
                'show_hide' => 1,
                'order' => 2,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            4 =>
            array(
                'id_feature' => 5,
                'feature_type' => 'Update',
                'feature_module' => 'Users',
                'show_hide' => 1,
                'order' => 2,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            5 =>
            array(
                'id_feature' => 6,
                'feature_type' => 'Delete',
                'feature_module' => 'Users',
                'show_hide' => 1,
                'order' => 2,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            6 =>
            array(
                'id_feature' => 7,
                'feature_type' => 'List',
                'show_hide' => 1,
                'order' => 3,
                'feature_module' => 'Log Activity',
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            7 =>
            array(
                'id_feature' => 8,
                'feature_type' => 'Detail',
                'feature_module' => 'Log Activity',
                'show_hide' => 1,
                'order' => 3,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            8 =>
            array(
                'id_feature' => 9,
                'feature_type' => 'List',
                'feature_module' => 'Admin Outlet',
                'show_hide' => 1,
                'order' => 4,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            9 =>
            array(
                'id_feature' => 10,
                'feature_type' => 'List',
                'feature_module' => 'Membership',
                'show_hide' => 0,
                'order' => 5,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            10 =>
            array(
                'id_feature' => 11,
                'feature_type' => 'Detail',
                'feature_module' => 'Membership',
                'show_hide' => 1,
                'order' => 5,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            11 =>
            array(
                'id_feature' => 12,
                'feature_type' => 'Create',
                'feature_module' => 'Membership',
                'show_hide' => 0,
                'order' => 5,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            12 =>
            array(
                'id_feature' => 13,
                'feature_type' => 'Update',
                'feature_module' => 'Membership',
                'show_hide' => 1,
                'order' => 5,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            13 =>
            array(
                'id_feature' => 14,
                'feature_type' => 'Delete',
                'feature_module' => 'Membership',
                'show_hide' => 0,
                'order' => 5,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            14 =>
            array(
                'id_feature' => 15,
                'feature_type' => 'List',
                'feature_module' => 'Greeting & Background',
                'show_hide' => 0,
                'order' => 6,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            15 =>
            array(
                'id_feature' => 16,
                'feature_type' => 'Create',
                'feature_module' => 'Greeting & Background',
                'show_hide' => 0,
                'order' => 6,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            16 =>
            array(
                'id_feature' => 17,
                'feature_type' => 'Update',
                'feature_module' => 'Greeting & Background',
                'show_hide' => 0,
                'order' => 6,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            17 =>
            array(
                'id_feature' => 18,
                'feature_type' => 'Delete',
                'feature_module' => 'Greeting & Background',
                'show_hide' => 0,
                'order' => 6,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            18 =>
            array(
                'id_feature' => 19,
                'feature_type' => 'List',
                'feature_module' => 'News',
                'show_hide' => 1,
                'order' => 7,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            19 =>
            array(
                'id_feature' => 20,
                'feature_type' => 'Detail',
                'feature_module' => 'News',
                'show_hide' => 1,
                'order' => 7,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            20 =>
            array(
                'id_feature' => 21,
                'feature_type' => 'Create',
                'feature_module' => 'News',
                'show_hide' => 1,
                'order' => 7,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            21 =>
            array(
                'id_feature' => 22,
                'feature_type' => 'Update',
                'feature_module' => 'News',
                'show_hide' => 1,
                'order' => 7,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            22 =>
            array(
                'id_feature' => 23,
                'feature_type' => 'Delete',
                'feature_module' => 'News',
                'show_hide' => 1,
                'order' => 7,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            23 =>
            array(
                'id_feature' => 24,
                'feature_type' => 'List',
                'feature_module' => 'Outlet',
                'show_hide' => 1,
                'order' => 8,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            24 =>
            array(
                'id_feature' => 25,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet',
                'show_hide' => 1,
                'order' => 8,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            25 =>
            array(
                'id_feature' => 26,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet',
                'show_hide' => 0,
                'order' => 8,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            26 =>
            array(
                'id_feature' => 27,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet',
                'show_hide' => 1,
                'order' => 8,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            27 =>
            array(
                'id_feature' => 28,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet',
                'show_hide' => 1,
                'order' => 8,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            28 =>
            array(
                'id_feature' => 29,
                'feature_type' => 'List',
                'feature_module' => 'Outlet Photo',
                'show_hide' => 1,
                'order' => 9,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            29 =>
            array(
                'id_feature' => 30,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet Photo',
                'show_hide' => 1,
                'order' => 9,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            30 =>
            array(
                'id_feature' => 31,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet Photo',
                'show_hide' => 1,
                'order' => 9,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            31 =>
            array(
                'id_feature' => 32,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Import',
                'show_hide' => 1,
                'order' => 10,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            32 =>
            array(
                'id_feature' => 33,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet Export',
                'show_hide' => 1,
                'order' => 10,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            33 =>
            array(
                'id_feature' => 34,
                'feature_type' => 'List',
                'feature_module' => 'Outlet Holiday',
                'show_hide' => 1,
                'order' => 11,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            34 =>
            array(
                'id_feature' => 35,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet Holiday',
                'show_hide' => 1,
                'order' => 11,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            35 =>
            array(
                'id_feature' => 36,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet Holiday',
                'show_hide' => 1,
                'order' => 11,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            36 =>
            array(
                'id_feature' => 37,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Holiday',
                'show_hide' => 1,
                'order' => 11,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            37 =>
            array(
                'id_feature' => 38,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet Holiday',
                'show_hide' => 1,
                'order' => 11,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            38 =>
            array(
                'id_feature' => 39,
                'feature_type' => 'Detail',
                'feature_module' => 'Outlet Admin',
                'show_hide' => 1,
                'order' => 12,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            39 =>
            array(
                'id_feature' => 40,
                'feature_type' => 'Create',
                'feature_module' => 'Outlet Admin',
                'show_hide' => 1,
                'order' => 12,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            40 =>
            array(
                'id_feature' => 41,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Admin',
                'show_hide' => 1,
                'order' => 12,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            41 =>
            array(
                'id_feature' => 42,
                'feature_type' => 'Delete',
                'feature_module' => 'Outlet Admin',
                'show_hide' => 1,
                'order' => 12,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            42 =>
            array(
                'id_feature' => 43,
                'feature_type' => 'List',
                'feature_module' => 'Product Category',
                'show_hide' => 1,
                'order' => 13,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            43 =>
            array(
                'id_feature' => 44,
                'feature_type' => 'Detail',
                'feature_module' => 'Product Category',
                'show_hide' => 1,
                'order' => 13,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            44 =>
            array(
                'id_feature' => 45,
                'feature_type' => 'Create',
                'feature_module' => 'Product Category',
                'show_hide' => 1,
                'order' => 13,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            45 =>
            array(
                'id_feature' => 46,
                'feature_type' => 'Update',
                'feature_module' => 'Product Category',
                'show_hide' => 1,
                'order' => 13,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            46 =>
            array(
                'id_feature' => 47,
                'feature_type' => 'Delete',
                'feature_module' => 'Product Category',
                'show_hide' => 1,
                'order' => 13,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            47 =>
            array(
                'id_feature' => 48,
                'feature_type' => 'List',
                'feature_module' => 'Product',
                'show_hide' => 1,
                'order' => 14,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            48 =>
            array(
                'id_feature' => 49,
                'feature_type' => 'Detail',
                'feature_module' => 'Product',
                'show_hide' => 1,
                'order' => 14,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            49 =>
            array(
                'id_feature' => 50,
                'feature_type' => 'Create',
                'feature_module' => 'Product',
                'show_hide' => 1,
                'order' => 14,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            50 =>
            array(
                'id_feature' => 51,
                'feature_type' => 'Update',
                'feature_module' => 'Product',
                'show_hide' => 1,
                'order' => 14,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            51 =>
            array(
                'id_feature' => 52,
                'feature_type' => 'Delete',
                'feature_module' => 'Product',
                'show_hide' => 1,
                'order' => 14,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            52 =>
            array(
                'id_feature' => 53,
                'feature_type' => 'List',
                'feature_module' => 'Product Photo',
                'show_hide' => 1,
                'order' => 15,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            53 =>
            array(
                'id_feature' => 54,
                'feature_type' => 'Create',
                'feature_module' => 'Product Photo',
                'show_hide' => 1,
                'order' => 15,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            54 =>
            array(
                'id_feature' => 55,
                'feature_type' => 'Delete',
                'feature_module' => 'Product Photo',
                'show_hide' => 1,
                'order' => 15,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            55 =>
            array(
                'id_feature' => 56,
                'feature_type' => 'Update',
                'feature_module' => 'Product Import',
                'show_hide' => 1,
                'order' => 16,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            56 =>
            array(
                'id_feature' => 57,
                'feature_type' => 'Detail',
                'feature_module' => 'Product Export',
                'show_hide' => 1,
                'order' => 17,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            57 =>
            array(
                'id_feature' => 58,
                'feature_type' => 'Update',
                'feature_module' => 'Grand Total Calculation Rule',
                'show_hide' => 1,
                'order' => 18,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            58 =>
            array(
                'id_feature' => 59,
                'feature_type' => 'Update',
                'feature_module' => 'Point Acquisition Setting',
                'show_hide' => 0,
                'order' => 19,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            59 =>
            array(
                'id_feature' => 60,
                'feature_type' => 'Update',
                'feature_module' => 'Cashback Acquisition Setting',
                'show_hide' => 0,
                'order' => 20,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            60 =>
            array(
                'id_feature' => 61,
                'feature_type' => 'Update',
                'feature_module' => 'Delivery Price Setting',
                'show_hide' => 0,
                'order' => 21,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            61 =>
            array(
                'id_feature' => 62,
                'feature_type' => 'Update',
                'feature_module' => 'Outlet Product Price Setting',
                'show_hide' => 1,
                'order' => 22,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            62 =>
            array(
                'id_feature' => 63,
                'feature_type' => 'Update',
                'feature_module' => 'Internal Courier Setting',
                'show_hide' => 0,
                'order' => 23,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            63 =>
            array(
                'id_feature' => 64,
                'feature_type' => 'List',
                'feature_module' => 'Manual Payment',
                'show_hide' => 0,
                'order' => 24,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            64 =>
            array(
                'id_feature' => 65,
                'feature_type' => 'Detail',
                'feature_module' => 'Manual Payment',
                'show_hide' => 0,
                'order' => 24,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            65 =>
            array(
                'id_feature' => 66,
                'feature_type' => 'Create',
                'feature_module' => 'Manual Payment',
                'show_hide' => 0,
                'order' => 24,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            66 =>
            array(
                'id_feature' => 67,
                'feature_type' => 'Update',
                'feature_module' => 'Manual Payment',
                'show_hide' => 0,
                'order' => 24,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            67 =>
            array(
                'id_feature' => 68,
                'feature_type' => 'Delete',
                'feature_module' => 'Manual Payment',
                'show_hide' => 0,
                'order' => 24,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            68 =>
            array(
                'id_feature' => 69,
                'feature_type' => 'List',
                'feature_module' => 'Transaction',
                'show_hide' => 1,
                'order' => 25,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            69 =>
            array(
                'id_feature' => 70,
                'feature_type' => 'Detail',
                'feature_module' => 'Transaction',
                'show_hide' => 1,
                'order' => 25,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            70 =>
            array(
                'id_feature' => 71,
                'feature_type' => 'List',
                'feature_module' => 'Point Log History',
                'show_hide' => 1,
                'order' => 26,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            71 =>
            array(
                'id_feature' => 72,
                'feature_type' => 'List',
                'feature_module' => 'Deals',
                'show_hide' => 1,
                'order' => 27,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            72 =>
            array(
                'id_feature' => 73,
                'feature_type' => 'Detail',
                'feature_module' => 'Deals',
                'show_hide' => 1,
                'order' => 27,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            73 =>
            array(
                'id_feature' => 74,
                'feature_type' => 'Create',
                'feature_module' => 'Deals',
                'show_hide' => 1,
                'order' => 27,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            74 =>
            array(
                'id_feature' => 75,
                'feature_type' => 'Update',
                'feature_module' => 'Deals',
                'show_hide' => 1,
                'order' => 27,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            75 =>
            array(
                'id_feature' => 76,
                'feature_type' => 'Delete',
                'feature_module' => 'Deals',
                'show_hide' => 1,
                'order' => 27,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            76 =>
            array(
                'id_feature' => 77,
                'feature_type' => 'List',
                'feature_module' => 'Inject Voucher',
                'show_hide' => 1,
                'order' => 28,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            77 =>
            array(
                'id_feature' => 78,
                'feature_type' => 'Detail',
                'feature_module' => 'Inject Voucher',
                'show_hide' => 1,
                'order' => 28,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            78 =>
            array(
                'id_feature' => 79,
                'feature_type' => 'Create',
                'feature_module' => 'Inject Voucher',
                'show_hide' => 1,
                'order' => 28,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            79 =>
            array(
                'id_feature' => 80,
                'feature_type' => 'Update',
                'feature_module' => 'Inject Voucher',
                'show_hide' => 1,
                'order' => 28,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            80 =>
            array(
                'id_feature' => 81,
                'feature_type' => 'Delete',
                'feature_module' => 'Inject Voucher',
                'show_hide' => 1,
                'order' => 28,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            81 =>
            array(
                'id_feature' => 82,
                'feature_type' => 'Update',
                'feature_module' => 'Text Replace',
                'show_hide' => 1,
                'order' => 29,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            82 =>
            array(
                'id_feature' => 83,
                'feature_type' => 'List',
                'feature_module' => 'Enquiries',
                'show_hide' => 1,
                'order' => 30,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            83 =>
            array(
                'id_feature' => 84,
                'feature_type' => 'Detail',
                'feature_module' => 'Enquiries',
                'show_hide' => 1,
                'order' => 30,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            84 =>
            array(
                'id_feature' => 85,
                'feature_type' => 'Update',
                'feature_module' => 'About Us',
                'show_hide' => 1,
                'order' => 31,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            85 =>
            array(
                'id_feature' => 86,
                'feature_type' => 'Update',
                'feature_module' => 'Terms Of Services',
                'show_hide' => 1,
                'order' => 32,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            86 =>
            array(
                'id_feature' => 87,
                'feature_type' => 'Update',
                'feature_module' => 'Contact Us',
                'show_hide' => 1,
                'order' => 33,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            87 =>
            array(
                'id_feature' => 88,
                'feature_type' => 'List',
                'feature_module' => 'Frequently Asked Question',
                'show_hide' => 1,
                'order' => 34,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            88 =>
            array(
                'id_feature' => 89,
                'feature_type' => 'Create',
                'feature_module' => 'Frequently Asked Question',
                'show_hide' => 1,
                'order' => 34,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            89 =>
            array(
                'id_feature' => 90,
                'feature_type' => 'Update',
                'feature_module' => 'Frequently Asked Question',
                'show_hide' => 1,
                'order' => 34,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            90 =>
            array(
                'id_feature' => 91,
                'feature_type' => 'Delete',
                'feature_module' => 'Frequently Asked Question',
                'show_hide' => 1,
                'order' => 34,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            91 =>
            array(
                'id_feature' => 92,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM User',
                'show_hide' => 1,
                'order' => 35,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            92 =>
            array(
                'id_feature' => 93,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Transaction',
                'show_hide' => 1,
                'order' => 36,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            93 =>
            array(
                'id_feature' => 94,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Enquiry',
                'show_hide' => 1,
                'order' => 37,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            94 =>
            array(
                'id_feature' => 95,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Deals',
                'show_hide' => 1,
                'order' => 38,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            95 =>
            array(
                'id_feature' => 96,
                'feature_type' => 'Update',
                'feature_module' => 'Text Replaces',
                'show_hide' => 1,
                'order' => 29,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            96 =>
            array(
                'id_feature' => 97,
                'feature_type' => 'Update',
                'feature_module' => 'Email Header & Footer',
                'show_hide' => 1,
                'order' => 39,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            97 =>
            array(
                'id_feature' => 98,
                'feature_type' => 'List',
                'feature_module' => 'Campaign',
                'show_hide' => 1,
                'order' => 40,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            98 =>
            array(
                'id_feature' => 99,
                'feature_type' => 'Detail',
                'feature_module' => 'Campaign',
                'show_hide' => 1,
                'order' => 40,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            99 =>
            array(
                'id_feature' => 100,
                'feature_type' => 'Create',
                'feature_module' => 'Campaign',
                'show_hide' => 1,
                'order' => 40,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            100 =>
            array(
                'id_feature' => 101,
                'feature_type' => 'Update',
                'feature_module' => 'Campaign',
                'show_hide' => 1,
                'order' => 40,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            101 =>
            array(
                'id_feature' => 102,
                'feature_type' => 'Delete',
                'feature_module' => 'Campaign',
                'show_hide' => 1,
                'order' => 40,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            102 =>
            array(
                'id_feature' => 103,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Email Queue',
                'show_hide' => 1,
                'order' => 41,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            103 =>
            array(
                'id_feature' => 104,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Email Sent',
                'show_hide' => 1,
                'order' => 42,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            104 =>
            array(
                'id_feature' => 105,
                'feature_type' => 'List',
                'feature_module' => 'Campaign SMS Queue',
                'show_hide' => 1,
                'order' => 43,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            105 =>
            array(
                'id_feature' => 106,
                'feature_type' => 'List',
                'feature_module' => 'Campaign SMS Sent',
                'show_hide' => 1,
                'order' => 44,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            106 =>
            array(
                'id_feature' => 107,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Push Queue',
                'show_hide' => 1,
                'order' => 45,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            107 =>
            array(
                'id_feature' => 108,
                'feature_type' => 'List',
                'feature_module' => 'Campaign Push Sent',
                'show_hide' => 1,
                'order' => 46,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            108 =>
            array(
                'id_feature' => 109,
                'feature_type' => 'List',
                'feature_module' => 'Promotion',
                'show_hide' => 1,
                'order' => 47,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            109 =>
            array(
                'id_feature' => 110,
                'feature_type' => 'Detail',
                'feature_module' => 'Promotion',
                'show_hide' => 1,
                'order' => 47,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            110 =>
            array(
                'id_feature' => 111,
                'feature_type' => 'Create',
                'feature_module' => 'Promotion',
                'show_hide' => 1,
                'order' => 47,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            111 =>
            array(
                'id_feature' => 112,
                'feature_type' => 'Update',
                'feature_module' => 'Promotion',
                'show_hide' => 1,
                'order' => 47,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            112 =>
            array(
                'id_feature' => 113,
                'feature_type' => 'Delete',
                'feature_module' => 'Promotion',
                'show_hide' => 1,
                'order' => 47,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            113 =>
            array(
                'id_feature' => 114,
                'feature_type' => 'List',
                'feature_module' => 'Inbox Global',
                'show_hide' => 1,
                'order' => 48,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            114 =>
            array(
                'id_feature' => 115,
                'feature_type' => 'Detail',
                'feature_module' => 'Inbox Global',
                'show_hide' => 1,
                'order' => 48,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            115 =>
            array(
                'id_feature' => 116,
                'feature_type' => 'Create',
                'feature_module' => 'Inbox Global',
                'show_hide' => 1,
                'order' => 48,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            116 =>
            array(
                'id_feature' => 117,
                'feature_type' => 'Update',
                'feature_module' => 'Inbox Global',
                'show_hide' => 1,
                'order' => 48,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            117 =>
            array(
                'id_feature' => 118,
                'feature_type' => 'Delete',
                'feature_module' => 'Inbox Global',
                'show_hide' => 1,
                'order' => 48,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            118 =>
            array(
                'id_feature' => 119,
                'feature_type' => 'List',
                'feature_module' => 'Auto CRM',
                'show_hide' => 1,
                'order' => 49,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            119 =>
            array(
                'id_feature' => 120,
                'feature_type' => 'Detail',
                'feature_module' => 'Auto CRM',
                'show_hide' => 1,
                'order' => 49,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            120 =>
            array(
                'id_feature' => 121,
                'feature_type' => 'Create',
                'feature_module' => 'Auto CRM',
                'show_hide' => 1,
                'order' => 49,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            121 =>
            array(
                'id_feature' => 122,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM',
                'show_hide' => 1,
                'order' => 49,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            122 =>
            array(
                'id_feature' => 123,
                'feature_type' => 'Delete',
                'feature_module' => 'Auto CRM',
                'show_hide' => 1,
                'order' => 49,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            123 =>
            array(
                'id_feature' => 124,
                'feature_type' => 'Update',
                'feature_module' => 'Advertisement',
                'show_hide' => 0,
                'order' => 50,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            124 =>
            array(
                'id_feature' => 125,
                'feature_type' => 'Report',
                'feature_module' => 'Report Global',
                'show_hide' => 1,
                'order' => 51,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            125 =>
            array(
                'id_feature' => 126,
                'feature_type' => 'Report',
                'feature_module' => 'Report Customer',
                'show_hide' => 1,
                'order' => 52,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            126 =>
            array(
                'id_feature' => 127,
                'feature_type' => 'Report',
                'feature_module' => 'Report Product',
                'show_hide' => 1,
                'order' => 53,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            127 =>
            array(
                'id_feature' => 128,
                'feature_type' => 'Report',
                'feature_module' => 'Report Outlet',
                'show_hide' => 1,
                'order' => 54,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            128 =>
            array(
                'id_feature' => 129,
                'feature_type' => 'Report',
                'feature_module' => 'Magic Report',
                'show_hide' => 0,
                'order' => 55,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            129 =>
            array(
                'id_feature' => 130,
                'feature_type' => 'List',
                'feature_module' => 'Reward',
                'show_hide' => 0,
                'order' => 56,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            130 =>
            array(
                'id_feature' => 131,
                'feature_type' => 'Detail',
                'feature_module' => 'Reward',
                'show_hide' => 0,
                'order' => 56,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            131 =>
            array(
                'id_feature' => 132,
                'feature_type' => 'Create',
                'feature_module' => 'Reward',
                'show_hide' => 0,
                'order' => 56,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            132 =>
            array(
                'id_feature' => 133,
                'feature_type' => 'Update',
                'feature_module' => 'Reward',
                'show_hide' => 0,
                'order' => 56,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            133 =>
            array(
                'id_feature' => 134,
                'feature_type' => 'Delete',
                'feature_module' => 'Reward',
                'show_hide' => 0,
                'order' => 56,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            134 =>
            array(
                'id_feature' => 135,
                'feature_type' => 'Create',
                'feature_module' => 'Spin The Wheel',
                'show_hide' => 0,
                'order' => 57,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            135 =>
            array(
                'id_feature' => 136,
                'feature_type' => 'Update',
                'feature_module' => 'Spin The Wheel',
                'show_hide' => 0,
                'order' => 57,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            136 =>
            array(
                'id_feature' => 137,
                'feature_type' => 'Delete',
                'feature_module' => 'Spin The Wheel',
                'show_hide' => 0,
                'order' => 57,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            137 =>
            array(
                'id_feature' => 138,
                'feature_type' => 'Update',
                'feature_module' => 'Spin The Wheel Setting',
                'show_hide' => 0,
                'order' => 58,
                'created_at' => '2018-05-10 08:00:00',
                'updated_at' => '2018-05-10 08:00:00',
            ),
            138 =>
            array(
                'id_feature' => 139,
                'feature_type' => 'List',
                'feature_module' => 'Deals Subscription',
                'show_hide' => 0,
                'order' => 59,
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            139 =>
            array(
                'id_feature' => 140,
                'feature_type' => 'Detail',
                'feature_module' => 'Deals Subscription',
                'show_hide' => 0,
                'order' => 59,
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            140 =>
            array(
                'id_feature' => 141,
                'feature_type' => 'Create',
                'feature_module' => 'Deals Subscription',
                'show_hide' => 0,
                'order' => 59,
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            141 =>
            array(
                'id_feature' => 142,
                'feature_type' => 'Update',
                'feature_module' => 'Deals Subscription',
                'show_hide' => 0,
                'order' => 59,
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            142 =>
            array(
                'id_feature' => 143,
                'feature_type' => 'Delete',
                'feature_module' => 'Deals Subscription',
                'show_hide' => 0,
                'order' => 59,
                'created_at' => '2018-12-12 08:00:00',
                'updated_at' => '2018-12-12 08:00:00',
            ),
            143 =>
            array(
                'id_feature' => 144,
                'feature_type' => 'List',
                'feature_module' => 'Banner',
                'show_hide' => 1,
                'order' => 60,
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            144 =>
            array(
                'id_feature' => 145,
                'feature_type' => 'Create',
                'feature_module' => 'Banner',
                'show_hide' => 1,
                'order' => 60,
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            145 =>
            array(
                'id_feature' => 146,
                'feature_type' => 'Update',
                'feature_module' => 'Banner',
                'show_hide' => 1,
                'order' => 60,
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            146 =>
            array(
                'id_feature' => 147,
                'feature_type' => 'Delete',
                'feature_module' => 'Banner',
                'show_hide' => 1,
                'order' => 60,
                'created_at' => '2018-12-14 08:00:00',
                'updated_at' => '2018-12-14 08:00:00',
            ),
            147 =>
            array(
                'id_feature' => 148,
                'feature_type' => 'Update',
                'feature_module' => 'User Profile Completing',
                'show_hide' => 1,
                'order' => 61,
                'created_at' => '2018-12-17 16:20:00',
                'updated_at' => '2018-12-17 16:20:00',
            ),
            148 =>
            array(
                'id_feature' => 149,
                'feature_type' => 'List',
                'feature_module' => 'Custom Page',
                'show_hide' => 1,
                'order' => 62,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            149 =>
            array(
                'id_feature' => 150,
                'feature_type' => 'Create',
                'feature_module' => 'Custom Page',
                'show_hide' => 1,
                'order' => 62,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            150 =>
            array(
                'id_feature' => 151,
                'feature_type' => 'Update',
                'feature_module' => 'Custom Page',
                'show_hide' => 1,
                'order' => 62,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            151 =>
            array(
                'id_feature' => 152,
                'feature_type' => 'Delete',
                'feature_module' => 'Custom Page',
                'show_hide' => 1,
                'order' => 62,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            152 =>
            array(
                'id_feature' => 153,
                'feature_type' => 'Detail',
                'feature_module' => 'Custom Page',
                'show_hide' => 1,
                'order' => 62,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            153 =>
            array(
                'id_feature' => 154,
                'feature_type' => 'Create',
                'feature_module' => 'Delivery Service',
                'show_hide' => 0,
                'order' => 63,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            154 =>
            array(
                'id_feature' => 155,
                'feature_type' => 'List',
                'feature_module' => 'Brand',
                'show_hide' => 1,
                'order' => 64,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            155 =>
            array(
                'id_feature' => 156,
                'feature_type' => 'Create',
                'feature_module' => 'Brand',
                'show_hide' => 1,
                'order' => 64,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            156 =>
            array(
                'id_feature' => 157,
                'feature_type' => 'Update',
                'feature_module' => 'Brand',
                'show_hide' => 1,
                'order' => 64,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            157 =>
            array(
                'id_feature' => 158,
                'feature_type' => 'Delete',
                'feature_module' => 'Brand',
                'show_hide' => 1,
                'order' => 64,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            158 =>
            array(
                'id_feature' => 159,
                'feature_type' => 'Detail',
                'feature_module' => 'Brand',
                'show_hide' => 1,
                'order' => 64,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            159 =>
            array(
                'id_feature' => 160,
                'feature_type' => 'List',
                'feature_module' => 'Text Menu',
                'show_hide' => 1,
                'order' => 65,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            160 =>
            array(
                'id_feature' => 161,
                'feature_type' => 'Update',
                'feature_module' => 'Text Menu',
                'show_hide' => 1,
                'order' => 65,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            161 =>
            array(
                'id_feature' => 162,
                'feature_type' => 'List',
                'feature_module' => 'Confirmation Messages',
                'show_hide' => 1,
                'order' => 66,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            162 =>
            array(
                'id_feature' => 163,
                'feature_type' => 'Update',
                'feature_module' => 'Confirmation Messages',
                'show_hide' => 1,
                'order' => 66,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            163 =>
            array(
                'id_feature' => 164,
                'feature_type' => 'List',
                'feature_module' => 'News Category',
                'show_hide' => 1,
                'order' => 67,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            164 =>
            array(
                'id_feature' => 165,
                'feature_type' => 'Create',
                'feature_module' => 'News Category',
                'show_hide' => 1,
                'order' => 67,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            165 =>
            array(
                'id_feature' => 166,
                'feature_type' => 'Update',
                'feature_module' => 'News Category',
                'show_hide' => 1,
                'order' => 67,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            166 =>
            array(
                'id_feature' => 167,
                'feature_type' => 'Delete',
                'feature_module' => 'News Category',
                'show_hide' => 1,
                'order' => 67,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            167 =>
            array(
                'id_feature' => 168,
                'feature_type' => 'List',
                'feature_module' => 'Intro',
                'show_hide' => 1,
                'order' => 68,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            168 =>
            array(
                'id_feature' => 169,
                'feature_type' => 'Create',
                'feature_module' => 'Intro',
                'show_hide' => 1,
                'order' => 68,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            169 =>
            array(
                'id_feature' => 170,
                'feature_type' => 'Update',
                'feature_module' => 'Intro',
                'show_hide' => 1,
                'order' => 68,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            170 =>
            array(
                'id_feature' => 171,
                'feature_type' => 'Delete',
                'feature_module' => 'Intro',
                'show_hide' => 1,
                'order' => 68,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            171 =>
            array(
                'id_feature' => 172,
                'feature_type' => 'Create',
                'feature_module' => 'Subscription',
                'show_hide' => 1,
                'order' => 69,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            172 =>
            array(
                'id_feature' => 173,
                'feature_type' => 'List',
                'feature_module' => 'Subscription',
                'show_hide' => 1,
                'order' => 69,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            173 =>
            array(
                'id_feature' => 174,
                'feature_type' => 'Detail',
                'feature_module' => 'Subscription',
                'show_hide' => 1,
                'order' => 69,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            174 =>
            array(
                'id_feature' => 175,
                'feature_type' => 'Update',
                'feature_module' => 'Subscription',
                'show_hide' => 1,
                'order' => 69,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            175 =>
            array(
                'id_feature' => 176,
                'feature_type' => 'Delete',
                'feature_module' => 'Subscription',
                'show_hide' => 1,
                'order' => 69,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            176 =>
            array(
                'id_feature' => 177,
                'feature_type' => 'Report',
                'feature_module' => 'Subscription',
                'show_hide' => 1,
                'order' => 69,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            177 =>
            array(
                'id_feature' => 178,
                'feature_type' => 'Update',
                'feature_module' => 'Auto CRM Subscription',
                'show_hide' => 1,
                'order' => 70,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            178 =>
            array(
                'id_feature' => 179,
                'feature_type' => 'List',
                'feature_module' => 'User Feedback',
                'show_hide' => 1,
                'order' => 71,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            179 =>
            array(
                'id_feature' => 180,
                'feature_type' => 'List',
                'feature_module' => 'Product Modifier',
                'show_hide' => 1,
                'order' => 72,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            180 =>
            array(
                'id_feature' => 181,
                'feature_type' => 'Create',
                'feature_module' => 'Product Modifier',
                'show_hide' => 1,
                'order' => 72,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            181 =>
            array(
                'id_feature' => 182,
                'feature_type' => 'Detail',
                'feature_module' => 'Product Modifier',
                'show_hide' => 1,
                'order' => 72,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            182 =>
            array(
                'id_feature' => 183,
                'feature_type' => 'Update',
                'feature_module' => 'Product Modifier',
                'show_hide' => 1,
                'order' => 72,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            183 =>
            array(
                'id_feature' => 184,
                'feature_type' => 'Delete',
                'feature_module' => 'Product Modifier',
                'show_hide' => 1,
                'order' => 72,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            184 =>
            array(
                'id_feature' => 185,
                'feature_type' => 'List',
                'feature_module' => 'Product Modifier Price',
                'show_hide' => 1,
                'order' => 73,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            185 =>
            array(
                'id_feature' => 186,
                'feature_type' => 'Update',
                'feature_module' => 'Product Modifier Price',
                'show_hide' => 1,
                'order' => 73,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            186 =>
                array(
                    'id_feature' => 187,
                    'feature_type' => 'List',
                    'feature_module' => 'Welcome Voucher',
                    'show_hide' => 1,
                    'order' => 74,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            187 =>
                array(
                    'id_feature' => 188,
                    'feature_type' => 'Detail',
                    'feature_module' => 'Welcome Voucher',
                    'show_hide' => 1,
                    'order' => 74,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            188 =>
                array(
                    'id_feature' => 189,
                    'feature_type' => 'Create',
                    'feature_module' => 'Welcome Voucher',
                    'show_hide' => 1,
                    'order' => 74,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            189 =>
                array(
                    'id_feature' => 190,
                    'feature_type' => 'Update',
                    'feature_module' => 'Welcome Voucher',
                    'show_hide' => 1,
                    'order' => 74,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            190 =>
                array(
                    'id_feature' => 191,
                    'feature_type' => 'Delete',
                    'feature_module' => 'Welcome Voucher',
                    'show_hide' => 1,
                    'order' => 74,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            191 =>
                array(
                    'id_feature' => 192,
                    'feature_type' => 'Update',
                    'feature_module' => 'Fraud Detection Settings',
                    'show_hide' => 1,
                    'order' => 75,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            192 =>
                array(
                    'id_feature' => 193,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Device',
                    'show_hide' => 1,
                    'order' => 76,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            193 =>
                array(
                    'id_feature' => 194,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Transaction Day',
                    'show_hide' => 1,
                    'order' => 77,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            194 =>
                array(
                    'id_feature' => 195,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Transaction Week',
                    'show_hide' => 1,
                    'order' => 78,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            195 =>
                array(
                    'id_feature' => 196,
                    'feature_type' => 'Update',
                    'feature_module' => 'List User Fraud',
                    'show_hide' => 1,
                    'order' => 79,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            196 =>
                array(
                    'id_feature' => 197,
                    'feature_type' => 'List',
                    'feature_module' => 'Maximum Order',
                    'show_hide' => 1,
                    'order' => 80,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            197 =>
                array(
                    'id_feature' => 198,
                    'feature_type' => 'Update',
                    'feature_module' => 'Maximum Order',
                    'show_hide' => 1,
                    'order' => 80,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            198 =>
            array(
                'id_feature' => 199,
                'feature_type' => 'Update',
                'feature_module' => 'Default Outlet',
                'show_hide' => 1,
                'order' => 81,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            199 => 
            array(
                'id_feature' => 200,
                'feature_type' => 'List',
                'feature_module' => 'Promo Campaign',
                'show_hide' => 1,
                'order' => 82,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            200 =>
            array(
                'id_feature' => 201,
                'feature_type' => 'Detail',
                'feature_module' => 'Promo Campaign',
                'show_hide' => 1,
                'order' => 82,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            201 =>
            array(
                'id_feature' => 202,
                'feature_type' => 'Create',
                'feature_module' => 'Promo Campaign',
                'show_hide' => 1,
                'order' => 82,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            202 =>
            array(
                'id_feature' => 203,
                'feature_type' => 'Update',
                'feature_module' => 'Promo Campaign',
                'show_hide' => 1,
                'order' => 82,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            203 =>
            array(
                'id_feature' => 204,
                'feature_type' => 'Delete',
                'feature_module' => 'Promo Campaign',
                'show_hide' => 1,
                'order' => 82,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            204 =>
            array(
                'id_feature' => 205,
                'feature_type' => 'List',
                'feature_module' => 'Point Injection',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            205 =>
            array(
                'id_feature' => 206,
                'feature_type' => 'Detail',
                'feature_module' => 'Point Injection',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            206 =>
            array(
                'id_feature' => 207,
                'feature_type' => 'Create',
                'feature_module' => 'Point Injection',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            207 =>
            array(
                'id_feature' => 208,
                'feature_type' => 'Update',
                'feature_module' => 'Point Injection',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            208 =>
            array(
                'id_feature' => 209,
                'feature_type' => 'Delete',
                'feature_module' => 'Point Injection',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            209 =>
            array(
                'id_feature' => 210,
                'feature_type' => 'Update',
                'feature_module' => 'Setting Phone',
                'show_hide' => 1,
                'order' => 82,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            210 =>
            array(
                'id_feature' => 211,
                'feature_type' => 'Detail',
                'feature_module' => 'User Feedback',
                'show_hide' => 0,
                'order' => 71,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            211 =>
            array(
                'id_feature' => 212,
                'feature_type' => 'List',
                'feature_module' => 'Feedback Rating Item',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            212 =>
            array(
                'id_feature' => 213,
                'feature_type' => 'Update',
                'feature_module' => 'Feedback Rating Item',
                'show_hide' => 1,
                'order' => 83,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            213 =>
                array(
                    'id_feature' => 214,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Transaction Point',
                    'show_hide' => 1,
                    'order' => 84,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            214 =>
                array(
                    'id_feature' => 215,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud In Between Transaction',
                    'show_hide' => 1,
                    'order' => 85,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            215 =>
                array(
                    'id_feature' => 216,
                    'feature_type' => 'Update',
                    'feature_module' => 'Referral',
                    'show_hide' => 0,
                    'order' => 86,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            216 =>
                array(
                    'id_feature' => 217,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Referral User',
                    'show_hide' => 0,
                    'order' => 87,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            217 =>
                array(
                    'id_feature' => 218,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Referral',
                    'show_hide' => 0,
                    'order' => 88,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            218 =>
                array(
                    'id_feature' => 219,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Fraud Promo Code',
                    'show_hide' => 1,
                    'order' => 89,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            219 =>
                array(
                    'id_feature' => 220,
                    'feature_type' => 'Update',
                    'feature_module' => 'Maintenance Mode Setting',
                    'show_hide' => 1,
                    'order' => 90,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            220 =>
            array(
                'id_feature' => 221,
                'feature_type' => 'List',
                'feature_module' => 'Achievement',
                'show_hide' => 1,
                'order' => 91,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            221 =>
            array(
                'id_feature' => 222,
                'feature_type' => 'Detail',
                'feature_module' => 'Achievement',
                'show_hide' => 1,
                'order' => 91,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            222 =>
            array(
                'id_feature' => 223,
                'feature_type' => 'Create',
                'feature_module' => 'Achievement',
                'show_hide' => 1,
                'order' => 91,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            223 =>
            array(
                'id_feature' => 224,
                'feature_type' => 'Update',
                'feature_module' => 'Achievement',
                'show_hide' => 1,
                'order' => 91,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            224 =>
            array(
                'id_feature' => 225,
                'feature_type' => 'Delete',
                'feature_module' => 'Achievement',
                'show_hide' => 1,
                'order' => 91,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            225 =>
            array(
                'id_feature' => 226,
                'feature_type' => 'Report',
                'feature_module' => 'Achievement',
                'show_hide' => 1,
                'order' => 91,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            226 =>
            array(
                'id_feature' => 227,
                'feature_type' => 'List',
                'feature_module' => 'Quest',
                'show_hide' => 1,
                'order' => 92,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            227 =>
            array(
                'id_feature' => 228,
                'feature_type' => 'Detail',
                'feature_module' => 'Quest',
                'show_hide' => 1,
                'order' => 92,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            228 =>
            array(
                'id_feature' => 229,
                'feature_type' => 'Create',
                'feature_module' => 'Quest',
                'show_hide' => 1,
                'order' => 92,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            229 =>
            array(
                'id_feature' => 230,
                'feature_type' => 'Update',
                'feature_module' => 'Quest',
                'show_hide' => 1,
                'order' => 92,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            230 =>
            array(
                'id_feature' => 231,
                'feature_type' => 'Delete',
                'feature_module' => 'Quest',
                'show_hide' => 1,
                'order' => 92,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            231 =>
            array(
                'id_feature' => 232,
                'feature_type' => 'Report',
                'feature_module' => 'Quest',
                'show_hide' => 1,
                'order' => 92,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            232 =>
            array(
                'id_feature' => 233,
                'feature_type' => 'Update',
                'feature_module' => 'Promo Cashback Setting',
                'show_hide' => 1,
                'order' => 93,
                'created_at' => date('Y-m-d H:00:00'),
                'updated_at' => date('Y-m-d H:00:00'),
            ),
            233 =>
                array(
                    'id_feature' => 234,
                    'feature_type' => 'List',
                    'feature_module' => 'List Disburse',
                    'show_hide' => 1,
                    'order' => 94,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            234 =>
                array(
                    'id_feature' => 235,
                    'feature_type' => 'Update',
                    'feature_module' => 'Settings Disburse',
                    'show_hide' => 1,
                    'order' => 95,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            235 =>
                array (
                    'id_feature' => 236,
                    'feature_type' => 'List',
                    'feature_module' => 'Product Promo Category',
                    'show_hide' => 1,
                    'order' => 96,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            236 =>
                array (
                    'id_feature' => 237,
                    'feature_type' => 'Detail',
                    'feature_module' => 'Product Promo Category',
                    'show_hide' => 1,
                    'order' => 96,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            237 =>
                array (
                    'id_feature' => 238,
                    'feature_type' => 'Create',
                    'feature_module' => 'Product Promo Category',
                    'show_hide' => 1,
                    'order' => 96,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            238 =>
                array (
                    'id_feature' => 239,
                    'feature_type' => 'Update',
                    'feature_module' => 'Product Promo Category',
                    'show_hide' => 1,
                    'order' => 96,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            239 =>
                array (
                    'id_feature' => 240,
                    'feature_type' => 'Delete',
                    'feature_module' => 'Product Promo Category',
                    'show_hide' => 1,
                    'order' => 96,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            240 =>
	            array (
	                'id_feature' => 241,
	                'feature_type' => 'List',
	                'feature_module' => 'Featured Subscription',
                    'show_hide' => 1,
                    'order' => 97,
	                'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
	            ),
            241 =>
            	array (
	                'id_feature' => 242,
	                'feature_type' => 'Create',
	                'feature_module' => 'Featured Subscription',
                    'show_hide' => 1,
                    'order' => 97,
	                'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
	            ),
            242 =>
	            array (
	                'id_feature' => 243,
	                'feature_type' => 'Update',
	                'feature_module' => 'Featured Subscription',
                    'show_hide' => 1,
                    'order' => 97,
	                'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
	            ),
            243 =>
	            array (
	                'id_feature' => 244,
	                'feature_type' => 'Delete',
	                'feature_module' => 'Featured Subscription',
                    'show_hide' => 1,
                    'order' => 97,
	                'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
	            ),
            244 =>
                array (
                    'id_feature' => 245,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Point Injection',
                    'show_hide' => 1,
                    'order' => 98,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            245 =>
                array (
                    'id_feature' => 246,
                    'feature_type' => 'Update',
                    'feature_module' => 'Setting Inbox User',
                    'show_hide' => 1,
                    'order' => 99,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            246 =>
                array (
                    'id_feature' => 247,
                    'feature_type' => 'List',
                    'feature_module' => 'List User Franchise',
                    'show_hide' => 1,
                    'order' => 100,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            247 =>
                array (
                    'id_feature' => 248,
                    'feature_type' => 'Update',
                    'feature_module' => 'User Franchise',
                    'show_hide' => 1,
                    'order' => 101,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            248 =>
                array(
                    'id_feature' => 249,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report GoSend',
                    'show_hide' => 1,
                    'order' => 102,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            249 =>
                array(
                    'id_feature' => 250,
                    'feature_type' => 'Update',
                    'feature_module' => 'Setting Payment Method',
                    'show_hide' => 1,
                    'order' => 103,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            250 =>
                array(
                    'id_feature' => 251,
                    'feature_type' => 'Update',
                    'feature_module' => 'Setting Time Expired OTP',
                    'show_hide' => 1,
                    'order' => 104,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            251 =>
                array(
                    'id_feature' => 252,
                    'feature_type' => 'Update',
                    'feature_module' => 'Setting Time Expired Email',
                    'show_hide' => 1,
                    'order' => 105,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            252 =>
                array(
                    'id_feature' => 253,
                    'feature_type' => 'Create',
                    'feature_module' => 'Payment Method',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            253 =>
                array(
                    'id_feature' => 254,
                    'feature_type' => 'List',
                    'feature_module' => 'Payment Method',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            254 =>
                array(
                    'id_feature' => 255,
                    'feature_type' => 'Update',
                    'feature_module' => 'Payment Method',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            255 =>
                array(
                    'id_feature' => 256,
                    'feature_type' => 'Delete',
                    'feature_module' => 'Payment Method',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            256 =>
                array(
                    'id_feature' => 257,
                    'feature_type' => 'Create',
                    'feature_module' => 'Payment Method Category',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            257 =>
                array(
                    'id_feature' => 258,
                    'feature_type' => 'List',
                    'feature_module' => 'Payment Method Category',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            258 =>
                array(
                    'id_feature' => 259,
                    'feature_type' => 'Update',
                    'feature_module' => 'Payment Method Category',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            259 =>
                array(
                    'id_feature' => 260,
                    'feature_type' => 'Delete',
                    'feature_module' => 'Payment Method Category',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            260 =>
                array(
                    'id_feature' => 261,
                    'feature_type' => 'List',
                    'feature_module' => 'Outlet Pin',
                    'show_hide' => 1,
                    'order' => 8,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            261 =>
                array(
                    'id_feature' => 262,
                    'feature_type' => 'Update',
                    'feature_module' => 'Order Auto Reject Time',
                    'show_hide' => 1,
                    'order' => 106,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            262 =>
                array(
                    'id_feature' => 263,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Payment',
                    'show_hide' => 1,
                    'order' => 109,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            263 =>
                array(
                    'id_feature' => 264,
                    'feature_type' => 'List',
                    'feature_module' => 'Welcome Subscription',
                    'show_hide' => 1,
                    'order' => 110,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            264 =>
                array(
                    'id_feature' => 265,
                    'feature_type' => 'Detail',
                    'feature_module' => 'Welcome Subscription',
                    'show_hide' => 1,
                    'order' => 110,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            265 =>
                array(
                    'id_feature' => 266,
                    'feature_type' => 'Create',
                    'feature_module' => 'Welcome Subscription',
                    'show_hide' => 1,
                    'order' => 110,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            266 =>
                array(
                    'id_feature' => 267,
                    'feature_type' => 'Update',
                    'feature_module' => 'Welcome Subscription',
                    'show_hide' => 1,
                    'order' => 110,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            267 =>
                array(
                    'id_feature' => 268,
                    'feature_type' => 'Delete',
                    'feature_module' => 'Welcome Subscription',
                    'show_hide' => 1,
                    'order' => 110,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            268 =>
                array(
                    'id_feature' => 269,
                    'feature_type' => 'Update',
                    'feature_module' => 'Start Deals',
                    'show_hide' => 1,
                    'order' => 111,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            269 =>
                array(
                    'id_feature' => 270,
                    'feature_type' => 'Update',
                    'feature_module' => 'Start Subscription',
                    'show_hide' => 1,
                    'order' => 112,
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
            270 =>
                array(
                    'id_feature' => 271,
                    'feature_type' => 'Report',
                    'feature_module' => 'Report Shift',
                    'show_hide' => 1,
                    'order' => 107,
                    'created_at' => '2018-05-10 08:00:00',
                    'updated_at' => '2018-05-10 08:00:00',
                ),
            271 =>
                array (
                    'id_feature' => 272,
                    'feature_type' => 'Update',
                    'feature_module' => 'Setting Timer ShopeePay',
                    'created_at' => date('Y-m-d H:00:00'),
                    'updated_at' => date('Y-m-d H:00:00'),
                ),
        ));
    }
}
