<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFranchise extends Model
{
    protected $table = 'user_franchises';
	protected $primaryKey = 'id_user_franchise';

	protected $fillable = [
		'id_user_franchise_seed',
	    'phone',
		'email',
        'user_franchise_type'
	];
}
