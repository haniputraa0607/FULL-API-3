<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TextReplace
 * 
 * @property int $id_text_replace
 * @property string $keyword
 * @property string $reference
 * @property string $type
 * @property string $default_value
 * @property string $custom_rule
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TextReplace extends Model
{
	protected $primaryKey = 'id_text_replace';

	protected $fillable = [
		'keyword',
		'reference',
		'type',
		'default_value',
		'custom_rule',
		'status'
	];
}
