<?php

namespace Modules\Favorite\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductModifier;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
	protected $primaryKey = 'id_favorite';

	protected $fillable = [
		'id_outlet',
		'id_product',
		'id_user',
		'notes',
		'product_qty'
	];

	public function modifiers(){
		return $this->belongsToMany(ProductModifier::class,'favorite_modifiers','id_favorite','id_product_modifier');
	}

	public function outlet(){
		return $this->belongsTo(Outlet::class,'id_outlet','id_outlet');
	}

	public function product(){
		return $this->belongsTo(Product::class,'id_product','id_product');
	}

	public function user(){
		return $this->belongsTo(User::class,'id_user','id_user');
	}

}
