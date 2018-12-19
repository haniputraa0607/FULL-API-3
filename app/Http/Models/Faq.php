<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Faq
 * 
 * @property int $id_faq
 * @property string $question
 * @property string $answer
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Faq extends Model
{
	protected $primaryKey = 'id_faq';

	protected $fillable = [
		'question',
		'answer'
	];
}
