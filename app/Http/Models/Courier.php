<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Courier
 * 
 * @property int $id_courier
 * @property string $short_name
 * @property string $name
 * @property string $status
 * @property string $courier_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Courier extends Model
{
	protected $table = 'courier';
	protected $primaryKey = 'id_courier';

	protected $fillable = [
		'short_name',
		'name',
		'status',
		'courier_type'
	];
}
