<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $primaryKey = 'id_banner';
	
	protected $fillable   = [
		'image',
		'id_news',
		'url',
		'position',
		'type',
		'banner_start',
		'banner_end'
	];

	public function news()
	{
		return $this->belongsTo(News::class, 'id_news', 'id_news');
	}
}
