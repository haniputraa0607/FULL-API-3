<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTag extends Model
{
    protected $primaryKey = 'id_product_tag';

	protected $casts = [
		'id_product' => 'int',
		'id_tag' => 'int',
	];

	protected $fillable = [
		'id_product',
		'id_tag',
		'created_at',
		'updated_at'
	];

	public function tag()
	{
		return $this->belongsTo(\App\Http\Models\Tag::class, 'id_tag');
	}

	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}
}
