<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DealTotal extends Model
{
	protected $primaryKey = 'id_deals_total';
    protected $table = 'deals_total';

	protected $fillable = [
		'id_deals',
		'deals_total'
	];
}
