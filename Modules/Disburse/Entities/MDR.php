<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class MDR extends Model
{
    protected $table = 'mdr';
	protected $primaryKey = 'id_mdr';

	protected $fillable = [
	    'payment_name',
		'mdr',
        'mdr_central',
        'percent_type',
        'charged'
	];
}
