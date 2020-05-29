<?php

namespace Modules\ShopeePay\Entities;

use Illuminate\Database\Eloquent\Model;

class ShopeePayMerchant extends Model
{
    public $primaryKey  = 'id_shopee_pay_merchant';
    protected $fillable = [
    	'merchant_name',
    	'merchant_host_id',
    	'merchant_ext_id',
    	'phone',
    	'email',
    	'logo',
    	'postal_code',
    	'city',
    	'state',
    	'district',
    	'ward',
    	'address',
    	'business_tax_id',
    	'national_id_type',
    	'national_id',
    	'additional_info',
    	'mcc',
    	'point_of_initiation',
    	'settlement_emails',
    	'withdrawal_option',
    	'status'
    ];
}
