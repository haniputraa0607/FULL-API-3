<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSetting extends Model
{
    protected $primaryKey = 'id_transaction_setting';

	protected $fillable = [
		'cashback_percent',
		'cashback_maximum',
		'created_at',
		'updated_at'
	];
}
