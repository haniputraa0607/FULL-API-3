<?php

return [
    'midtrans_gopay' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Gopay',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_gopay.png',
        'text'            => 'GoPay',
        'refund_time'     => 15,
        'midtrans'        => 1
    ],
    'midtrans_credit_card'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Credit Card',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Debit/Credit Card',
        'refund_time'     => 15,
        'midtrans'        => 1
    ],
    'midtrans_bank_transfer'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Bank Transfer',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_banktransfer.png',
        'text'            => 'Bank Transfer',
        'refund_time'     => 15,
        'midtrans'        => 1
    ],
    'midtrans_akulaku'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'akulaku',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_akulaku.png',
        'text'            => 'Akulaku',
        'refund_time'     => 15,
        'midtrans'        => 1
    ],
    'midtrans_qris'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'shopeepay-qris',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopeepay-qris.png',
        'text'            => 'ShopeePay/e-Wallet Lainnya',
        'refund_time'     => 15,
        'midtrans'        => 1
    ],
    'ipay88_cc'      => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Credit Card',
        'status'          => 0, //'credit_card_payment_gateway:Ipay88',
        'logo'            => 0,
        'text'            => 'Debit/Credit Card',
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ],
        'refund_time'     => 15
    ],
    'ipay88_ovo'     => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ],
        'refund_time'     => 15
    ],
    'ovo'            => [
        'payment_gateway' => 'Ovo',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'refund_time'     => 15
    ],
    'shopeepay'      => [
        'payment_gateway' => 'Shopeepay',
        'payment_method'  => 'Shopeepay',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay',
        'available_time'    => [
            'start' => '03:00',
            'end'   => '23:45',
        ],
        'refund_time'     => 15
    ],
    'online_payment' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Midtrans',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_online_payment.png',
        'text'            => 'Online Payment'
    ],
    'xendit_ovo'          => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Ovo',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'refund_time'     => 15
    ],
    'xendit_dana'         => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Dana',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_dana.png',
        'text'            => 'DANA',
        'refund_time'     => 15
    ],
    'xendit_linkaja'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Linkaja',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_linkaja.png',
        'text'            => 'LinkAJa',
        'refund_time'     => 15
    ],
    'xendit_shopeepay'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'SHOPEEPAY',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay',
        'refund_time'     => 15
    ],
    'xendit_kredivo'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'KREDIVO',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_kredivo.png',
        'text'            => 'Kredivo',
        'refund_time'     => 15
    ],
    'xendit_qris'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'QRIS',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_qris.png',
        'text'            => 'QRIS',
        'refund_time'     => 15
    ],
    'xendit_credit_card'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'CREDIT_CARD',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API').'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Credit Card',
        'refund_time'     => 15
    ],
];
