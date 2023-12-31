<?php

return [
    'cash' => [
        'payment_gateway' => 'Cash',
        'payment_method'  => 'Cash',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_cash.png',
        'type'            => 'cash',
        'text'            => 'Tunai',
    ],
    'midtrans_gopay' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Gopay',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_gopay.png',
        'type'            => 'e-payment',
        'text'            => 'GoPay',
    ],
    'midtrans_cc'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Credit Card',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_creditcard.png',
        'type'            => 'e-payment',
        'text'            => 'Debit/Credit Card',
    ],
    'midtrans_banktransfer'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Bank Transfer',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_banktransfer.png',
        'type'            => 'e-payment',
        'text'            => 'Bank Transfer',
    ],
    'midtrans_akulaku'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'akulaku',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_akulaku.png',
        'type'            => 'e-payment',
        'text'            => 'Akulaku',
    ],
    'midtrans_qris'    => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'shopeepay-qris',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_shopeepay-qris.png',
        'type'            => 'e-payment',
        'text'            => 'ShopeePay/e-Wallet Lainnya',
    ],
    'ipay88_cc'      => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Credit Card',
        'status'          => 0, //'credit_card_payment_gateway:Ipay88',
        'logo'            => 0,
        'type'            => 'e-payment',
        'text'            => 'Debit/Credit Card',
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ]
    ],
    'ipay88_ovo'     => [
        'payment_gateway' => 'Ipay88',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_ovo_pay.png',
        'type'            => 'e-payment',
        'text'            => 'OVO',
        'available_time'    => [
            'start' => '00:00',
            'end'   => '23:45',
        ]
    ],
    'ovo'            => [
        'payment_gateway' => 'Ovo',
        'payment_method'  => 'Ovo',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_ovo_pay.png',
        'type'            => 'e-payment',
        'text'            => 'OVO',
    ],
    'shopeepay'      => [
        'payment_gateway' => 'Shopeepay',
        'payment_method'  => 'Shopeepay',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_shopee_pay.png',
        'type'            => 'e-payment',
        'text'            => 'ShopeePay',
        'available_time'    => [
            'start' => '03:00',
            'end'   => '23:45',
        ]
    ],
    'online_payment' => [
        'payment_gateway' => 'Midtrans',
        'payment_method'  => 'Midtrans',
        'status'          => 0,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_online_payment.png',
        'type'            => 'e-payment',
        'text'            => 'Online Payment',
    ],
    'xendit_ovo'          => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Ovo',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_ovo_pay.png',
        'type'            => 'e-payment',
        'text'            => 'OVO',
    ],
    'xendit_dana'         => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Dana',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_dana.png',
        'type'            => 'e-payment',
        'text'            => 'DANA',
    ],
    'xendit_linkaja'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'Linkaja',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_linkaja.png',
        'type'            => 'e-payment',
        'text'            => 'LinkAJa',
    ],
    'xendit_shopeepay'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'SHOPEEPAY',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_shopee_pay.png',
        'type'            => 'e-payment',
        'text'            => 'ShopeePay',
    ],
    'xendit_kredivo'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'KREDIVO',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_kredivo.png',
        'type'            => 'e-payment',
        'text'            => 'Kredivo',
    ],
    'xendit_qris'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'QRIS',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_qris.png',
        'type'            => 'e-payment',
        'text'            => 'QRIS',
    ],
    'xendit_credit_card'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'CREDIT_CARD',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_creditcard.png',
        'type'            => 'e-payment',
        'text'            => 'Credit Card',
    ],
    'xendit_bank_transfer'      => [
        'payment_gateway' => 'Xendit',
        'payment_method'  => 'BANK_TRANSFER',
        'status'          => 1,
        'logo'            => env('STORAGE_URL_DEFAULT_IMAGE').'default_image/payment_method/ic_banktransfer.png',
        'type'            => 'e-payment',
        'text'            => 'Virtual Account',
    ],
];
