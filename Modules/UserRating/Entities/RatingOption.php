<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class RatingOption extends Model
{
    protected $primaryKey = 'id_rating_option';
    protected $fillable = ['star','question','options','rating_target'];
}
