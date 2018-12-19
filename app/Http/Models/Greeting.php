<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Greeting
 * 
 * @property int $id_greetings
 * @property string $when
 * @property string $greeting
 * @property string $greeting2
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Greeting extends Model
{
	protected $primaryKey = 'id_greetings';

	protected $fillable = [
		'when',
		'greeting',
		'greeting2'
	];
}
