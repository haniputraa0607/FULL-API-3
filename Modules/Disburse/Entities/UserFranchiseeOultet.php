<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFranchiseeOultet extends Model
{
    protected $table = 'user_franchisee_outlet';
	protected $primaryKey = 'id_user_franchisee_outlet';

	protected $fillable = [
	    'id_user_franchisee',
		'id_outlet'
	];
}
