<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class NewsCategory extends Model
{
    protected $primaryKey = 'id_news_category';

    /**
     * @var array
     */
    protected $fillable = [
    	'category_name',
	];

	public function news(){
		return $this->hasMany(\App\Http\Models\News::class,'id_news_category');
	}
}
