<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 06 Aug 2020 15:39:29 +0700.
 */

namespace Modules\RedirectComplex\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;
// use Wildside\Userstamps\Userstamps;
/**
 * Class RedirectComplexReference
 * 
 * @property int $id_redirect_complex_reference
 * @property string $type
 * @property string $outlet_type
 * @property string $promo_type
 * @property string $promo_reference
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection $redirect_complex_outlets
 * @property \Illuminate\Database\Eloquent\Collection $redirect_complex_products
 *
 * @package Modules\RedirectComplex\Entities
 */
class RedirectComplexReference extends Eloquent
{
	// use Userstamps;
	protected $primaryKey = 'id_redirect_complex_reference';

	protected $appends  = [
		// 'get_promo'
	];

	protected $fillable = [
		'type',
		'name',
		'outlet_type',
		'promo_type',
		'promo_reference',
		'payment_method',
		'use_product'
	];

	public function redirect_complex_outlets()
	{
		return $this->hasMany(\Modules\RedirectComplex\Entities\RedirectComplexOutlet::class, 'id_redirect_complex_reference');
	}

	public function redirect_complex_products()
	{
		return $this->hasMany(\Modules\RedirectComplex\Entities\RedirectComplexProduct::class, 'id_redirect_complex_reference');
	}

	public function outlets()
	{
		return $this->belongsToMany(\App\Http\Models\Outlet::class, 'redirect_complex_outlets', 'id_redirect_complex_reference', 'id_outlet')
					->withPivot('id_outlet')
					->withTimestamps()->orderBy('id_outlet', 'DESC');
	}

	public function brands()
	{
		return $this->belongsToMany(\Modules\Brand\Entities\Brand::class, 'redirect_complex_brands', 'id_redirect_complex_reference', 'id_brand')
					->withPivot('id_brand')
					->withTimestamps()->orderBy('id_brand', 'DESC');
	}

	public function products()
	{
		return $this->belongsToMany(\App\Http\Models\Product::class, 'redirect_complex_products', 'id_redirect_complex_reference', 'id_product')
					->select('product_categories.*','products.*')
					->leftJoin('product_categories', 'product_categories.id_product_category', '=', 'products.id_product_category')
					->withPivot('id_redirect_complex_product', 'qty', 'id_brand')
					->withTimestamps();
	}

	public function promo_campaign()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaign::class, 'promo_reference', 'id_promo_campaign');
	}

	public function getGetPromoAttribute() {

        if( $this->promo_type == 'promo_campaign')
        {	
			$this->load(['promo_campaign.promo_campaign_promo_codes' => function($q) {
							$q->first();
						}]);
        }
    }
}
