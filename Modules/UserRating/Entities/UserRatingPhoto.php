<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRatingPhoto extends Model
{
    protected $primaryKey = 'id_user_rating_photo';
    protected $appends    = ['url_user_rating_photo'];
    protected $fillable = ['id_user_rating','user_rating_photo'];

    public function getUrlUserRatingPhotoAttribute()
    {
        if (!empty($this->user_rating_photo)) {
            return config('url.storage_url_api') . $this->user_rating_photo;
        }
    }
}
