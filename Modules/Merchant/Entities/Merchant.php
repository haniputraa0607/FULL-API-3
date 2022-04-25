<?php

namespace Modules\Merchant\Entities;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $table = 'merchants';
	protected $primaryKey = 'id_merchant';

	protected $fillable = [
	    'id_user',
		'id_outlet',
        'merchant_status',
        'merchant_pic_name',
        'merchant_pic_id_card_number',
        'merchant_pic_email',
        'merchant_pic_phone',
        'merchant_completed_step',
        'merchant_count_transaction'
	];
}
