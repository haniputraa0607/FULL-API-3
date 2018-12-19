<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HomeBackground
 * 
 * @property int $id_home_background
 * @property string $when
 * @property string $picture
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class HomeBackground extends Model
{
	protected $primaryKey = 'id_home_background';

	protected $fillable = [
		'when',
		'picture'
	];
}
