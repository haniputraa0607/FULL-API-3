<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Setting
 * 
 * @property int $id_setting
 * @property string $key
 * @property string $value
 * @property string $value_text
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Setting extends Model
{
	protected $primaryKey = 'id_setting';

	protected $fillable = [
		'key',
		'value',
		'value_text'
	];
}
