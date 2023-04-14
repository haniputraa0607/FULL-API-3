<?php

return [
    'midtrans_gopay' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Gopay',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_gopay.png',
        'text'            => 'GoPay',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'midtrans_credit_card'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Credit Card',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Debit/Credit Card',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'midtrans_bank_transfer'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Bank Transfer',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_banktransfer.png',
        'text'            => 'Bank Transfer',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'midtrans_akulaku'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'akulaku',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_akulaku.png',
        'text'            => 'Akulaku',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'midtrans_qris'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'shopeepay-qris',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_shopeepay-qris.png',
        'text'            => 'ShopeePay/e-Wallet Lainnya',
        'refund_time'     => 15,
        'redirect'        => true
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
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'ipay88_ovo'     => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ],
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'ovo'            => [
        'payment_gateway' => 'Ovo',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'shopeepay'      => [
        'payment_gateway' => 'Shopeepay',
        'payment_method'  => 'Shopeepay',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay',
        'available_time'    => [
            'start' => '03:00',
            'end'   => '23:45',
        ],
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'online_payment' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Midtrans',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_online_payment.png',
        'text'            => 'Online Payment',
        'redirect'        => true
    ],
    'xendit_ovo'          => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Ovo',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_ovo_pay.png',
        'text'            => 'OVO',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_dana'         => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Dana',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_dana.png',
        'text'            => 'DANA',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_linkaja'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Linkaja',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_linkaja.png',
        'text'            => 'LinkAJa',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_shopeepay'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'SHOPEEPAY',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_shopee_pay.png',
        'text'            => 'ShopeePay',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_kredivo'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'KREDIVO',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_kredivo.png',
        'text'            => 'Kredivo',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_qris'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'QRIS',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_qris.png',
        'text'            => 'QRIS',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_credit_card'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'CREDIT_CARD',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_creditcard.png',
        'text'            => 'Credit Card',
        'refund_time'     => 15,
        'redirect'        => true
    ],
    'xendit_bca'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'BCA',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_bca.png',
        'text'            => 'BCA',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_mandiri'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'MANDIRI',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_mandiri.png',
        'text'            => 'Mandiri',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_bni'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'BNI',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_bni.png',
        'text'            => 'BNI',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_bri'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'BRI',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_bri.png',
        'text'            => 'BRI',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_bjb'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'BJB',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_bjb.png',
        'text'            => 'BJB',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_bsi'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'BSI',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_bsi.png',
        'text'            => 'BSI',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_permata'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'PERMATA',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_permata.png',
        'text'            => 'Permata',
        'refund_time'     => 15,
        'redirect'        => false
    ],
    'xendit_bss'      => [
        'payment_gateway' => 'Xendit VA',
        'payment_method'  => 'SAHABAT_SAMPOERNA',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_API') . 'default_image/payment_method/ic_sahabat_sampoerna.png',
        'text'            => 'Bank Sahabat Sampoerna',
        'refund_time'     => 15,
        'redirect'        => false
    ],
];
