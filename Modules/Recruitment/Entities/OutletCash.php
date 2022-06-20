<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCash extends Model
{
    protected $table = 'outlet_cash';
	protected $primaryKey = 'id_outlet_cash';

	protected $fillable = [
        'id_outlet',
	    'id_hairstylist_log_balance',
		'id_user_hair_stylist',
        'outlet_cash_type',
		'outlet_cash_amount',
		'outlet_cash_description',
		'outlet_cash_code',
        'outlet_cash_status',
        'confirm_at',
        'confirm_by'
	];
}
