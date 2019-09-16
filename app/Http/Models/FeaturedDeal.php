<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedDeal extends Model
{
	protected $primaryKey = 'id_featured_deals';

	protected $fillable = [
		'id_deals',
		'start_date',
		'end_date',
		'order'
	];

	public function deals(){
		return $this->belongsTo(\App\Http\Models\Deal::class,'id_deals');
	}
}
