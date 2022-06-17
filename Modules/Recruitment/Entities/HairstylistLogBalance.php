<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistLogBalance extends Model
{
    protected $table = 'hairstylist_log_balances';
	protected $primaryKey = 'id_hairstylist_log_balance';

	protected $fillable = [
		'id_user_hair_stylist',
		'balance',
		'balance_before',
		'balance_after',
		'id_reference',
		'source',
		'grand_total',
		'enc',
        'transfer_status',
        'transfer_code'
	];
}
