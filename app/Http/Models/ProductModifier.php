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

	public function products()
	{
		return $this->hasMany(\App\Http\Models\Product::class, 'id_product_modifier');
	}
}
