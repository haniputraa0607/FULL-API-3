<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserOutlet
 * 
 * @property int $id_user
 * @property int $id_outlet
 * @property string $enquiry
 * @property string $pickup_order
 * @property string $delivery
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class UserOutlet extends Model
{
	protected $primaryKey = 'id_user_outlet';
	
	public $incrementing = false;
	
	protected $casts = [
		'id_user' => 'int',
		'id_outlet' => 'int'
	];

	protected $fillable = [
		'phone',
		'email',
		'name',
		'id_outlet',
		'enquiry',
		'pickup_order',
		'delivery',
		'payment'
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
}
