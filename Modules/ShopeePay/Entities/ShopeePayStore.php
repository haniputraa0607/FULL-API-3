<?php

namespace Modules\ShopeePay\Entities;

use Illuminate\Database\Eloquent\Model;

class ShopeePayStore extends Model
{
    public $primaryKey  = 'id_shopee_pay_store';
    protected $fillable = [
    	'id_outlet',
    	'store_name',
    	'store_id',
    	'merchant_host_id',
    	'merchant_ext_id',
    	'store_ext_id',
    	'phone',
    	'email',
    	'logo',
    	'postal_code',
    	'city',
    	'state',
    	'district',
    	'ward',
    	'address',
    	'gps_longitude',
    	'gps_latitude',
    	'terminal_ids',
    	'mcc',
    	'nmid',
    	'merchant_criteria',
    	'additional_info',
    	'point_of_initiation',
    	'settlement_emails',
    	'status'
    ];
}
