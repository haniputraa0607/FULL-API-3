<?php

namespace Modules\Deals\Entities;

use Illuminate\Database\Eloquent\Model;

class DealsBrand extends Model
{
	public $timestamps = false;
    protected $fillable = [
    	'id_brand',
    	'id_deals'
    ];

    public function deals()
	{
        return $this->belongsTo(\Modules\Deals\Entities\Deal::class, 'id_deals', 'id_deals');
	}
}
