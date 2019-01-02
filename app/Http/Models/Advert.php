<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Advert extends Model
{
	protected $table = 'adverts';

	protected $primaryKey = 'id_advert';
	
	protected $fillable   = [
		'id_news',
		'page',
		'value',
		'type',
		'order'
	];

	public function news()
	{
		return $this->belongsTo(\App\Http\Models\News::class, 'id_news');
	}
}
