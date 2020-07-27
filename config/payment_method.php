<?php

return [
    'midtrans_gopay' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Gopay',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_gopay.png',
        'text'            => 'GoPay'
    ],
    'midtrans_cc'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Credit Card',
        'status'          => 'credit_card_payment_gateway:Midtrans',
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Debit/Credit Card'
    ],
    'ipay88_cc'      => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Credit Card',
        'status'          => 'credit_card_payment_gateway:Ipay88',
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Debit/Credit Card'
    ],
    'ipay88_ovo'     => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Ovo',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO'
    ],
    'ovo'            => [
        'payment_gateway' => 'Ovo',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO'
    ],
    'shopeepay'      => [
        'payment_gateway' => 'Shopeepay',
        'payment_method'  => 'Shopeepay',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay'
    ],
];
