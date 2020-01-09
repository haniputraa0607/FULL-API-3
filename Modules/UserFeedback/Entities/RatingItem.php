<?php

namespace Modules\UserFeedback\Entities;

use Illuminate\Database\Eloquent\Model;

class RatingItem extends Model
{
	protected $primaryKey = 'id_rating_item';
    protected $fillable = [
    	'image',
    	'image_selected',
    	'text'
    ];
    public function getImageAttribute($value)
    {
    	return env('S3_URL_API').$value;
    }
    public function getImageSelectedAttribute($value)
    {
    	return env('S3_URL_API').$value;
    }
}
