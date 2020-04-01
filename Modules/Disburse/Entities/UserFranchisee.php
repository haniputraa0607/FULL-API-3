<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFranchisee extends Model
{
	protected $primaryKey = 'id_user_franchisee';

	protected $fillable = [
	    'phone',
		'email'
	];
}
