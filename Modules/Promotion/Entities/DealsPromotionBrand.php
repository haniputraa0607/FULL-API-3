<?php

namespace Modules\Promotion\Entities;

use Illuminate\Database\Eloquent\Model;

class DealsPromotionBrand extends Model
{
	public $timestamps = false;
    protected $fillable = [
    	'id_brand',
    	'id_deals'
    ];

    public function deals_promotion_template()
	{
		return $this->belongsTo(\App\Http\Models\DealsPromotionTemplate::class, 'id_deals');
	}

	public function brand()
	{
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class, 'id_brand');
	}
}