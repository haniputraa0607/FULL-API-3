<?php

namespace Modules\UserFeedback\Entities;

use Illuminate\Database\Eloquent\Model;

class RatingItem extends Model
{
	protected $primaryKey = 'id_rating_item';
    protected $fillable = [
    	'image',
    	'image_selected',
    	'text',
        'rating_value',
        'order'
    ];
    public function getImageAttribute($value)
    {
        if($value){
            return env('S3_URL_API').$value;
        }
        return '';
    }
    public function getImageSelectedAttribute($value)
    {
        if($value){
            return env('S3_URL_API').$value;
        }
        return '';
    }
}
