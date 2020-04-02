<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFranchisee extends Model
{
    protected $table = 'user_franchises';
	protected $primaryKey = 'id_user_franchisee';

	protected $fillable = [
	    'phone',
		'email'
	];
}
