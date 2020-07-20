<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFranchiseOultet extends Model
{
    protected $table = 'user_franchise_outlet';
	protected $primaryKey = 'id_user_franchise_outlet';

	protected $fillable = [
	    'id_user_franchise',
		'id_outlet',
        'status_franchise'
	];
}
