<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModifier extends Model
{
    protected $hidden = ['pivot'];
    
	protected $primaryKey = 'id_product_modifier';

	protected $casts = [
		'id_product' => 'int'
	];

	protected $fillable = [
		'id_product',
		'type',
		'code',
		'text',
		'created_at',
		'updated_at'
	];

	public function products(){
		return $this->belongsToMany(Product::class,'product_modifier_products','id_product_modifier','id_product');
	}
	public function brands(){
		return $this->belongsToMany(Product::class,'product_modifier_brands','id_product_modifier','id_brand');
	}
	public function product_categories(){
		return $this->belongsToMany(Product::class,'product_modifier_product_categories','id_product_modifier','id_product_category');
	}
}
