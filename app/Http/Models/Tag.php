<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $primaryKey = 'id_tag';

	protected $fillable = [
		'tag_name',
		'created_at',
		'updated_at'
	];

	public function product_tags()
	{
		return $this->hasMany(\App\Http\Models\ProductTag::class, 'id_tag');
	}
}
