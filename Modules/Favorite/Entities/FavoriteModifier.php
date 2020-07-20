<?php

namespace Modules\Favorite\Entities;

use Illuminate\Database\Eloquent\Model;

class FavoriteModifier extends Model
{
	public $timestamps = false;

    protected $fillable = [
    	'id_favorite',
    	'qty',
    	'id_product_modifier'
    ];

    public function favorite(){
    	return $this->belongsTo(Favorite::class,'id_favorite');
    }
}
