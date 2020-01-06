<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class UserExtraToken extends Model
{
    protected $table = 'user_extra_token';
    
    protected $primaryKey = 'id_user_extra_token';

	protected $fillable = [
		'id_user',
		'extra_token'
	];
}
