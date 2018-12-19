<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionSent extends Model
{
	protected $primaryKey = 'id_promotion_sent';

	protected $casts = [
		'id_promotion' => 'int',
		'id_promotion_content' => 'int',
		'id_user' => 'int',
	];

	protected $fillable = [
		'id_promotion',
		'id_promotion_content',
		'id_user',
		'series_no',
		'send_at',
		'channel_email',
		'channel_sms',
		'channel_push',
		'channel_inbox',
		'email_read',
		'id_deals_voucher',
		'created_at',
		'updated_at',
	];

	public function promotion()
	{
		return $this->belongsTo(\App\Http\Models\Promotion::class, 'id_promotion');
	}
	
	public function content()
	{
		return $this->belongsTo(\App\Http\Models\PromotionContent::class, 'id_promotion_content');
	}

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}
}
