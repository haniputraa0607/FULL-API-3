<?php

namespace Modules\Favorite\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductPrice;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
	protected $primaryKey = 'id_favorite';

    protected $hidden = ['pivot'];

	protected $fillable = [
		'id_outlet',
		'id_product',
		'id_user',
		'notes',
		'product_qty'
	];

	protected $appends = ['product'];

	public function modifiers(){
		return $this->belongsToMany(ProductModifier::class,'favorite_modifiers','id_favorite','id_product_modifier');
	}

	public function outlet(){
		return $this->belongsTo(Outlet::class,'id_outlet','id_outlet');
	}

	public function product(){
		return $this->belongsTo(Product::class,'id_product','id_product');
	}

	protected function getProductPrice($id_outlet,$id_product,$product_qty){
		return ProductPrice::where([
			'id_outlet'=>$id_outlet,
			'id_product'=>$id_product
		])->pluck('product_price')->first()*$product_qty;
	}

	public function getProductAttribute(){
		$id_outlet = $this->id_outlet;
		$id_product = $this->id_product;
		$product_qty = $this->product_qty;
		$product = Product::select('id_product','product_name')->where([
			'id_product'=>$id_product
		])->with([
			'photos' => function($query){
				$query->select('id_product','product_photo')->limit(1);
			}
		])->first();
		return [
			'product_name' => $product->product_name,
			'url_product_photo' => $product->photos[0]->url_product_photo,
			'price' => $this->getProductPrice($id_outlet,$id_product,$product_qty)
		];
	}

	public function user(){
		return $this->belongsTo(User::class,'id_user','id_user');
	}

}
