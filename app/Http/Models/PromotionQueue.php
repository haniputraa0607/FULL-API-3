<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionQueue extends Model
{
	protected $primaryKey = 'id_promotion_queue';

	protected $casts = [
		'id_promotion' => 'int',
		'id_promotion_content' => 'int',
		'id_user' => 'int',
	];

	protected $fillable = [
		'id_promotion',
		'id_promotion_content',
		'id_user',
		'priority',
		'send_at',
		'created_at',
		'updated_at',
	];

	function user() 
	{
		return $this->belongsTo(User::class, 'id_user', 'id')->select('id', 'name', 'phone', 'email', 'gender', 'phone_verified', 'email_verified', 'level', 'birthday', 'points');
	}
	
	public function content()
	{
		return $this->belongsTo(\App\Http\Models\PromotionContent::class, 'id_promotion_content');
	}
}
