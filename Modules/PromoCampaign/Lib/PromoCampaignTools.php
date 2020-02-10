<?php

namespace Modules\PromoCampaign\Lib;

use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferral;
use App\Http\Models\Product;
use App\Http\Models\ProductModifier;
use App\Http\Models\UserDevice;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\Setting;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;

use App\Lib\MyHelper;

class PromoCampaignTools{

    function __construct()
    {
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
    }
	/**
	 * validate transaction to use promo campaign
	 * @param  	int 		$id_promo 	id promo campaigm
	 * @param  	array 		$trxs      	array of item and total transaction
	 * @param  	array 		$error     	error message
	 * @return 	array/boolean     modified array of trxs if can, otherwise false
	 */
	public function validatePromo($id_promo, $id_outlet, $trxs, &$errors, $source='promo_campaign'){
		/**
		 $trxs=[
			{
				id_product:1,
				qty:2
			}
		 ]
		 */
		if(!is_numeric($id_promo)){
			$errors[]='Id promo not valid';
			return false;
		}
		if(!is_array($trxs)){
			$errors[]='Transaction data not valid';
			return false;
		}

		if ($source == 'promo_campaign') 
		{
			$promo=PromoCampaign::with('promo_campaign_outlets')->find($id_promo);
			$promo_outlet = $promo->promo_campaign_outlets;
		}
		elseif($source == 'deals')
		{
			$promo=Deal::with('outlets_active')->find($id_promo);
			$promo_outlet = $promo->outlets_active;
		}
		else
		{
			$errors[]='Promo not found';
			return false;
		}

		if(!$promo){
			$errors[]='Promo not found';
			return false;
		}

		$outlet = $this->checkOutletRule($id_outlet, $promo->is_all_outlet??0, $promo_outlet);

		if(!$outlet){
			$errors[]='Promo cannot be used at this outlet';
			return false;
		}
		if(strtotime($promo->date_start??$promo->deals_start)>time()||strtotime($promo->date_end??$promo->deals_end)<time()){
			$errors[]='Promo is not valid';
			return false;
		}
		
		$discount=0;
		// add product discount if exist
		foreach ($trxs as  $id_trx => &$trx) {
			$product=Product::with(['product_prices' => function($q) use ($id_outlet){ 
							$q->where('id_outlet', '=', $id_outlet)
							  ->where('product_status', '=', 'Active')
							  ->where('product_stock_status', '=', 'Available');
						} ])->find($trx['id_product']);
			//is product available
			if(!$product){
				// product not available
				$errors[]='Product with id '.$trx['id_product'].' could not be found';
				continue;
			}
			$product_discount=$this->getProductDiscount($product)*$trx['qty'];
			$product_price=$product->product_prices[0]->product_price??[];
			// $discount+=$product_discount;
			if($product_discount){
				// $trx['discount']=$product_discount;
				$trx['new_price']=($product_price*$trx['qty'])-$product_discount;
			}
		}

		//get all modifier in array
		$mod = [];
		foreach ($trxs as $key => $value) {
			foreach ($value['modifiers'] as $key2 => $value2) {
				$mod[] = $value2;
			}
		}
		// remove duplicate modifiers
		$mod = array_flip($mod);
		$mod = array_flip($mod);
		// get all modifier data
		$mod = $this->getAllModifier($mod, $id_outlet);

		// get mod price 
		$mod_price =[];
		foreach ($mod as $key => $value) {
			$mod_price[$value['id_product_modifier']] = $value['product_modifier_price']??0;
		}

		switch ($promo->promo_type) {
			case 'Product discount':
				// load required relationship
				$promo->load($source.'_product_discount',$source.'_product_discount_rules');
				$promo_rules=$promo[$source.'_product_discount_rules'];
				$max_product = $promo_rules->max_product;
				$qty_promo_available = [];

				if(!$promo_rules->is_all_product){
					$promo_product=$promo[$source.'_product_discount']->toArray();
				}else{
					$promo_product="*";
				}

				// sum total quantity of same product, if greater than max product assign value to max product
				// get all modifier price total, index array of item, and qty for each modifier
				$item_get_promo = [];
				$mod_price_per_item = [];
				$mod_price_qty_per_item = [];
				foreach ($trxs as $key => $value) 
				{
					if (isset($item_get_promo[$value['id_product']])) 
					{
						if ( ($item_get_promo[$value['id_product']] + $value['qty']) >= $max_product) {
							$item_get_promo[$value['id_product']] = $max_product;
						}else{
							$item_get_promo[$value['id_product']] += $value['qty'];
						}
					}
					else
					{
						if ($value['qty'] >= $max_product) {
							$item_get_promo[$value['id_product']] = $max_product;
						}else{
							$item_get_promo[$value['id_product']] = $value['qty'];
						}
					}

					$mod_price_qty_per_item[$value['id_product']][$key] = [];
					$mod_price_qty_per_item[$value['id_product']][$key]['qty'] = $value['qty'];
					$mod_price_qty_per_item[$value['id_product']][$key]['price'] = 0;
					$mod_price_per_item[$value['id_product']][$key] = 0;

					foreach ($value['modifiers'] as $key2 => $value2) 
					{
						$mod_price_qty_per_item[$value['id_product']][$key]['price'] += $mod_price[$value2]??0;
						$mod_price_per_item[$value['id_product']][$key] += $mod_price[$value2]??0;
					}

				}

				// sort mod price qty ascending
				foreach ($mod_price_qty_per_item as $key => $value) {

					//sort price only to get index key
					asort($mod_price_per_item[$key]);
					
					// sort mod by price
					$keyPositions = [];
					foreach ($mod_price_per_item[$key] as $key2 => $row) {
						$keyPositions[] = $key2;
					}

					foreach ($value as $key2 => $row) {
					    $price[$key][$key2]  = $row['price'];
					}

					array_multisort($price[$key], SORT_ASC, $value);


					$sortedArray = [];
					foreach ($value as $key2 => $row) {
					    $sortedArray[$keyPositions[$key2]] = $row;
					}

					// assign sorted value to current mod key
					$mod_price_qty_per_item[$key] = $sortedArray;
				}

				// check promo qty for each item
				foreach ($mod_price_qty_per_item as $key => $value) 
				{
					foreach ($value as $key2 => &$value2) 
					{
						if ($value2['qty'] > 0) {
							if (($item_get_promo[$key] - $value2['qty']) > 0) 
							{
								$trxs[$key2]['promo_qty'] = $value2['qty'];
								$item_get_promo[$key] -= $value2['qty'];
							}
							else
							{
								$trxs[$key2]['promo_qty'] = $item_get_promo[$key];
								$item_get_promo[$key] = 0;
							}
						}
					}
				}

				foreach ($trxs as  $id_trx => &$trx) {

					// continue if qty promo for same product is all used 
					if ($trx['promo_qty'] == 0) {
						continue;
					}

					$modifier = 0;
					foreach ($trx['modifiers'] as $key2 => $value2) 
					{
						$modifier += $mod_price[$value2]??0;
					}

					// is all product get promo
					if($promo_rules->is_all_product){
						// get product data
						$product=Product::with(['product_prices' => function($q) use ($id_outlet){ 
							$q->where('id_outlet', '=', $id_outlet)
							  ->where('product_status', '=', 'Active')
							  ->where('product_stock_status', '=', 'Available')
							  ->where('product_visibility', '=', 'Visible');
						} ])->find($trx['id_product']);
						//is product available
						if(!$product){
							// product not available
							$errors[]='Product with id '.$trx['id_product'].' could not be found';
							continue;
						}
						// add discount
						$discount+=$this->discount_product($product,$promo_rules,$trx, $modifier);
					}else{
						// is product available in promo
						if(is_array($promo_product)&&in_array($trx['id_product'],array_column($promo_product,'id_product'))){
							// get product data
							$product=Product::with(['product_prices' => function($q) use ($id_outlet){ 
								$q->where('id_outlet', '=', $id_outlet)
								  ->where('product_status', '=', 'Active')
								  ->where('product_stock_status', '=', 'Available')
								  ->where('product_visibility', '=', 'Visible');
							} ])->find($trx['id_product']);
							//is product available
							if(!$product){
								// product not available
								$errors[]='Product with id '.$trx['id_product'].' could not be found';
								continue;
							}
							// add discount
							$discount+=$this->discount_product($product,$promo_rules,$trx, $modifier);
						}
					}
				}

				if($discount<=0){
					$message = $this->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>'product bertanda khusus']);

					$errors[]= $message;
					return false;
				}
				break;

			case 'Tier discount':
				// load requirement relationship
				$promo->load($source.'_tier_discount_rules',$source.'_tier_discount_product');
				$promo_product=$promo[$source.'_tier_discount_product'];
				$promo_product->load('product');
				if(!$promo_product){
					$errors[]='Tier discount promo product is not set correctly';
					return false;
				}

				// sum total quantity of same product
				foreach ($trxs as $key => $value) 
				{
					if (isset($item_get_promo[$value['id_product']])) 
					{
						$item_get_promo[$value['id_product']] += $value['qty'];
					}
					else
					{
						$item_get_promo[$value['id_product']] = $value['qty'];
					}
				}

				// get min max required for error message
				$promo_rules=$promo[$source.'_tier_discount_rules'];
				$min_qty = 1;
				$max_qty = 1;
				foreach ($promo_rules as $rule) {
					if($min_qty===null||$rule->min_qty<$min_qty){
						$min_qty=$rule->min_qty;
					}
					if($max_qty===null||$rule->max_qty>$max_qty){
						$max_qty=$rule->max_qty;
					}
				}

				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					return false;
				}
				//get cart's product to apply promo
				$product=null;
				foreach ($trxs as &$trx) {
					//is this the cart product we looking for?
					if($trx['id_product']==$promo_product->id_product){
						//set reference to this cart product
						$product=&$trx;
						// break from loop
						break;
					}
				}
				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					return false;
				}
				//find promo
				$promo_rule=false;
				$min_qty=null;
				$max_qty=null;
				foreach ($promo_rules as $rule) {
					if($min_qty===null||$rule->min_qty<$min_qty){
						$min_qty=$rule->min_qty;
					}
					if($max_qty===null||$rule->max_qty>$max_qty){
						$max_qty=$rule->max_qty;
					}
					if($rule->min_qty>$item_get_promo[$promo_product->id_product]){
						continue;
					}
					if($rule->max_qty<$item_get_promo[$promo_product->id_product]){
						continue;
					}
					$promo_rule=$rule;
				}
				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					return false;
				}
				// count discount
				foreach ($trxs as $key => &$trx) {

					$modifier = 0;
					foreach ($trx['modifiers'] as $key2 => $value2) 
					{
						$modifier += $mod_price[$value2]??0;
					}

					if($trx['id_product']==$promo_product->id_product){
						$trx['promo_qty'] = $trx['qty'];
						$discount+=$this->discount_product($promo_product->product,$promo_rule,$trx, $modifier);
					}
				}

				break;

			case 'Buy X Get Y':
				// load requirement relationship
				$promo->load($source.'_buyxgety_rules',$source.'_buyxgety_product_requirement');
				$promo_product=$promo[$source.'_buyxgety_product_requirement'];
				$promo_product->load('product');

				if(!$promo_product){
					$errors[]='Benefit product is not set correctly';
					return false;
				}

				// sum total quantity of same product
				foreach ($trxs as $key => $value) 
				{
					if (isset($item_get_promo[$value['id_product']])) 
					{
						$item_get_promo[$value['id_product']] += $value['qty'];
					}
					else
					{
						$item_get_promo[$value['id_product']] = $value['qty'];
					}
				}

				$promo_rules=$promo[$source.'_buyxgety_rules'];
				$min_qty=1;
				$max_qty=1;
				// get min max for error message
				foreach ($promo_rules as $rule) {

					if($min_qty===null||$rule->min_qty_requirement<$min_qty){
						$min_qty=$min_qty;
					}
					if($max_qty===null||$rule->max_qty_requirement>$max_qty){
						$max_qty=$max_qty;
					}
				}

				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					return false;
				}
				//get cart's product to get benefit
				$product=null;
				foreach ($trxs as &$trx) {
					//is this the cart product we looking for?
					if($trx['id_product']==$promo_product->id_product){
						//set reference to this cart product
						$product=&$trx;
						// break from loop
						break;
					}
				}
				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					return false;
				}
				//find promo
				$promo_rules=$promo[$source.'_buyxgety_rules'];
				$promo_rule=false;
				$min_qty=null;
				$max_qty=null;

				foreach ($promo_rules as $rule) {
					// search y product in cart
					$benefit_qty=$rule->benefit_qty;
					$min_req=$rule->min_qty_requirement;
					$max_req=$rule->max_qty_requirement;

					if($min_qty===null||$rule->min_qty_requirement<$min_qty){
						$min_qty=$min_req;
					}
					if($max_qty===null||$rule->max_qty_requirement>$max_qty){
						$max_qty=$max_req;
					}
					if($min_req>$item_get_promo[$promo_product->id_product]){
						continue;
					}
					if($max_req<$item_get_promo[$promo_product->id_product]){
						continue;
					}
					$promo_rule=$rule;
				}

				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					return false;
				}
				$benefit_product=Product::with(['brands','product_prices' => function($q) use ($id_outlet){ 
							$q->where('id_outlet', '=', $id_outlet)
							  ->where('product_status', '=', 'Active')
							  ->where('product_stock_status', '=', 'Available')
							  ->where('product_visibility', '=', 'Visible');
						} ])->find($promo_rule->benefit_id_product);
				$benefit_qty=$promo_rule->benefit_qty;
				$benefit_value=$promo_rule->discount_value;
				$benefit_type = $promo_rule->discount_type;
				$benefit_max_value = $promo_rule->max_percent_discount;

				if(!$benefit_product){
					$errors[]="Product benefit not found.";
					return false;
				}
				$benefit=null;

				$rule=(object) [
					'max_qty'=>$benefit_qty,
					'discount_type'=>$benefit_type,
					'discount_value'=>$benefit_value,
					'max_percent_discount'=>$benefit_max_value
				];

				// add product benefit
				$benefit_item = [
					'id_custom' 	=> isset(end($trxs)['id_custom']) ? end($trxs)['id_custom']+1 : '',
					'id_product'	=> $benefit_product->id_product,
					'id_brand'		=> $benefit_product->brands[0]->id_brand??'',
					'qty'			=> $promo_rule->benefit_qty,
					'is_promo'		=> 1,
					'is_free'		=> ($promo_rule->discount_type == "percent" && $promo_rule->discount_value == 100) ? 1 : 0,
					'modifiers'		=> []
				];
				// $benefit_item['id_product']	= $benefit_product->id_product;
				// $benefit_item['id_brand'] 	= $benefit_product->brands[0]->id_brand??'';
				// $benefit_item['qty'] 		= $promo_rule->benefit_qty;

				$discount+=$this->discount_product($benefit_product,$rule,$benefit_item);

				// return $benefit_item;
				array_push($trxs, $benefit_item);
				// return $trxs;
				break;

			case 'Discount global':
				// load required relationship
				$promo->load('promo_campaign_discount_global_rule');
				$promo_rules=$promo->promo_campaign_discount_global_rule;
				// get jumlah harga
				$total_price=0;
				foreach ($trxs as  $id_trx => &$trx) {
					$product=Product::with(['product_prices' => function($q) use ($id_outlet){ 
							$q->where('id_outlet', '=', $id_outlet)
							  ->where('product_status', '=', 'Active')
							  ->where('product_stock_status', '=', 'Available')
							  ->where('product_visibility', '=', 'Visible');
						} ])->find($trx['id_product']);
					$qty=$trx['qty'];
					$total_price+=$qty*$product->product_prices[0]->product_price??[];
				}
				if($promo_rules->discount_type=='Percent'){
					$discount+=($total_price*$promo_rules->discount_value)/100;
				}else{
					if($promo_rules->discount_value<$total_price){
						$discount += $promo_rules->discount_value;
					}else{
						$discount += $total_price;
					}
					break;
				}
				break;
			case 'Referral':
				$promo->load('promo_campaign_referral');
				$promo_rules=$promo->promo_campaign_referral;
				if($promo_rules->referred_promo_type == 'Product Discount'){
					$rule=(object) [
						'max_qty'=>false,
						'discount_type'=>$promo_rules->referred_promo_unit,
						'discount_value'=>$promo_rules->referred_promo_value,
						'max_percent_discount'=>$promo_rules->referred_promo_value_max
					];
					foreach ($trxs as  $id_trx => &$trx) {
						// get product data
						$product=Product::with(['product_prices' => function($q) use ($id_outlet){ 
							$q->where('id_outlet', '=', $id_outlet)
							  ->where('product_status', '=', 'Active')
							  ->where('product_stock_status', '=', 'Available');
						} ])->find($trx['id_product']);
						$cur_mod_price = 0;
						foreach ($trx['modifiers'] as $modifier) {
			                $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
			                $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
			                $cur_mod_price += ($mod_price[$id_product_modifier]??0)*$qty_product_modifier;
						}
						//is product available
						if(!$product){
							// product not available
							$errors[]='Product with id '.$trx['id_product'].' could not be found';
							continue;
						}
						// add discount
						$discount += $this->discount_product($product,$rule,$trx,$cur_mod_price);
					}
				}else{
					return [
						'item'=>$trxs,
						'discount'=>0
					];
				}
		}
		// discount?
		// if($discount<=0){
		// 	$errors[]='Does not get any discount';
		// 	return false;
		// }
		return [
			'item'=>$trxs,
			'discount'=>$discount
		];
	}

	/**
	 * validate transaction to use promo campaign light version
	 * @param  	int 		$id_promo 	id promo campaigm
	 * @param  	array 		$trxs      	array of item and total transaction
	 * @param  	array 		$error     	error message
	 * @return 	boolean     true/false
	 */

	public static function validatePromoLight($id_promo,$trxs,&$errors){
		/**
		 $trxs=[
			{
				id_product:1,
				qty:2
			}
		 ]
		 */
		if(!is_numeric($id_promo)){
			$errors[]='Id promo not valid';
			return false;
		}
		if(!is_array($trxs)){
			$errors[]='Transaction data not valid';
			return false;
		}
		$promo=PromoCampaign::find($id_promo);
		if(!$promo){
			$errors[]='Promo Campaign not found';
			return false;
		}
		$discount=0;
		switch ($promo->promo_type) {
			case 'Product discount':
				// load required relationship
				$promo->load('promo_campaign_product_discount','promo_campaign_product_discount_rules');
				$promo_rules=$promo->promo_campaign_product_discount_rules;
				if(!$promo_rules->is_all_product){
					$promo_product=$promo->promo_campaign_product_discount->toArray();
				}else{
					$promo_product="*";
				}
				foreach ($trxs as  $id_trx => &$trx) {
					// is all product get promo
					if($promo_rules->is_all_product){
						return true;
					}else{
						// is product available in promo
						if(is_array($promo_product)&&in_array($trx['id_product'],array_column($promo_product,'id_product'))){
							return true;
						}
					}
				}
				return false;
				break;

			case 'Tier discount':
				// load requirement relationship
				$promo->load('promo_campaign_tier_discount_rules','promo_campaign_tier_discount_product');
				$promo_product=$promo->promo_campaign_tier_discount_product;
				if(!$promo_product){
					$errors[]='Tier discount promo product is not set correctly';
					return false;
				}
				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$errors[]='Cart doesn\'t contain promoted product';
					return false;
				}
				//get cart's product to apply promo
				$product=null;
				foreach ($trxs as &$trx) {
					//is this the cart product we looking for?
					if($trx['id_product']==$promo_product->id_product){
						//set reference to this cart product
						$product=&$trx;
						// break from loop
						break;
					}
				}
				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$errors[]='Cart doesn\'t contain promoted product';
					return false;
				}
				return true;
				break;

			case 'Buy X Get Y':
				// load requirement relationship
				$promo->load('promo_campaign_buyxgety_rules','promo_campaign_buyxgety_product_requirement');
				$promo_product=$promo->promo_campaign_buyxgety_product_requirement;
				if(!$promo_product){
					$errors[]='Benefit product is not set correctly';
					return false;
				}
				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$errors[]='Requirement product doesnt available in cart';
					return false;
				}
				//get cart's product to get benefit
				$product=null;
				foreach ($trxs as &$trx) {
					//is this the cart product we looking for?
					if($trx['id_product']==$promo_product->id_product){
						//set reference to this cart product
						$product=&$trx;
						// break from loop
						break;
					}
				}
				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$errors[]='Requirement product doesnt available in cart';
					return false;
				}
				return true;
				break;

			case 'Discount global':
				return true;
				break;
		}
	}

	/**
	 * modify $trx set discount to product
	 * @param  Product 								$product
	 * @param  PromoCampaignProductDiscountRule 	$promo_rules
	 * @param  Array 								$trx 			transaction data
	 * @return int discount
	 */
	protected function discount_product($product,$promo_rules,&$trx, $modifier=null){
		// check discount type
		$discount=0;
		// set quantity of product to apply discount
		$discount_qty=$trx['qty'];
		$old=$trx['discount']??0;
		// is there any max qty set?
		if(($promo_rules->max_qty??false)&&$promo_rules->max_qty<$discount_qty){
			$discount_qty=$promo_rules->max_qty;
		}
		
		// check 'product discount' limit product qty
		if(($promo_rules->max_product??false)&&$promo_rules->max_product<$discount_qty){
			$discount_qty=$promo_rules->max_product;
		}

		// check if isset promo qty
		if (isset($trx['promo_qty'])) {
			$discount_qty = $trx['promo_qty'];
			unset($trx['promo_qty']);
		}
		$product_price = ($product->product_prices[0]->product_price??null)+$modifier;
		if(isset($trx['new_price'])&&$trx['new_price']){
			$product_price=$trx['new_price']/$trx['qty'];
		}
		if($promo_rules->discount_type=='Nominal' || $promo_rules->discount_type=='nominal'){
			$discount=$promo_rules->discount_value*$discount_qty;
			$trx['discount']=($trx['discount']??0)+$discount;
			$trx['new_price']=($product_price*$trx['qty'])-$trx['discount'];
			$trx['is_promo']=1;
		}else{
			// percent
			$discount_per_product = ($promo_rules->discount_value/100)*$product_price;
			if ($discount_per_product > $promo_rules->max_percent_discount) {
				$discount_per_product = $promo_rules->max_percent_discount;
			}
			$discount=(int)($discount_per_product*$discount_qty);
			$trx['discount']=($trx['discount']??0)+$discount;
			$trx['new_price']=($product_price*$trx['qty'])-$trx['discount'];
			$trx['is_promo']=1;
		}
		if($trx['new_price']<0){
			$trx['is_promo']=1;
			$trx['new_price']=0;
			$trx['discount']=$product_price*$discount_qty;
			$discount=$trx['discount']-$old;
		}
		return $discount;
	}

	/**
	 * Validate if a user can use promo
	 * @param  int 		$id_promo id promo campaign
	 * @param  int 		$id_user  id user
	 * @return boolean	true/false
	 */
	public function validateUser($id_promo, $id_user, $phone, $device_type, $device_id, &$errors=[],$id_code=null){
		$promo=PromoCampaign::find($id_promo);

		if(!$promo){
        	$errors[]='Promo campaign not found';
    		return false;
		}
		if(!$promo->step_complete || !$promo->user_type){
        	$errors[]='Promo campaign not finished';
    		return false;
		}

		if($promo->promo_type == 'Referral'){
			if(User::find($id_user)->transaction_online){
	        	$errors[]='Kode promo tidak ditemukan';
				return false;
			}
			if(UserReferralCode::where([
				'id_promo_campaign_promo_code'=>$id_code,
				'id_user'=>$id_user
			])->exists()){
	        	$errors[]='Kode promo tidak ditemukan';
	    		return false;
			}
	        $referer = UserReferralCode::where('id_promo_campaign_promo_code',$id_code)
	            ->join('users','users.id','=','user_referral_codes.id_user')
	            ->where('users.is_suspended','=',0)
	            ->first();
	        if(!$referer){
	        	$errors[] = 'Kode promo tidak ditemukan';
	        }
		}

		//check user 
		$user = $this->userFilter($id_user, $promo->user_type, $promo->specific_user, $phone);

        if(!$user){
        	$errors[]='User not found';
    		return false;
        }

        // use promo code?
        if($promo->limitation_usage){
        	// limit usage user?
        	if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('id_user',$id_user)->count()>=$promo->limitation_usage){
	        	$errors[]='Kuota anda untuk penggunaan kode promo ini telah habis';
	    		return false;
        	}

        	// limit usage device
        	if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('device_id',$device_id)->count()>=$promo->limitation_usage){
	        	$errors[]='Kuota device anda untuk penggunaan kode promo ini telah habis';
	    		return false;
        	}
        }
        return true;
	}

	/**
	 * Get product price with product discount
	 * @param  Product $product product
	 * @return int          new product price
	 */
	public function getProductDiscount($product){
		$product->load('discountActive');
		$productItem=$product->toArray();
		$productItem['discountActive']=$productItem['discount_active'];
    	$countSemen=0;
        if (count($productItem['discountActive']) > 0) {
            $productItem['discount_status'] = 'yes';
        } else {
            $productItem['discount_status'] = 'no';
        }
        if ($productItem['discount_status'] == 'yes') {
            foreach ($productItem['discountActive'] as $row => $dis) {
                if (!empty($dis['discount_percentage'])) {
                    $jat = $dis['discount_percentage'];

                    $count = $productItem['product_prices'][0]['product_price']??[] * $jat / 100;
                } else {
                    $count = $dis['discount_nominal'];
                }

                $now = date('Y-m-d');
                $time = date('H:i:s');
                $day = date('l');

                if ($now < $dis['discount_start']) {
                    $count = 0;
                }

                if ($now > $dis['discount_end']) {
                    $count = 0;
                }

                if ($time < $dis['discount_time_start']) {
                    $count = 0;
                }

                if ($time > $dis['discount_time_end']) {
                    $count = 0;
                }

                if (strpos($dis['discount_days'], $day) === false) {
                    $count = 0;
                }

                $countSemen += $count;
                $count = 0;
            }
        }
        if($countSemen>$productItem['product_prices'][0]['product_price']??[]){
        	$countSemen=$productItem['product_prices'][0]['product_price']??[];
        }
        return $countSemen;
    }

    public function userFilter($id_user, $rule, $valid_user, $phone)
    {
    	if ($rule == 'New user') 
    	{
    		$check = Transaction::where('id_user', '=', $id_user)->first();
    		if ($check) {
    			return false;
    		}
    	}
    	elseif ($rule == 'Specific user') 
    	{
    		$valid_user = explode(',', $valid_user);
    		if (!in_array($phone, $valid_user)) {
    			return false;
    		}
    	}

    	return true;
    }

    function checkOutletRule($id_outlet, $rule, $outlet = null)
    {
        if ($rule == '1') 
        {
            return true;
        } 
        elseif ($rule == '0') 
        {
            foreach ($outlet as $value) 
            {
                if ( $value['id_outlet'] == $id_outlet ) 
                {
                    return true;
                } 
            }

            return false;
        } 
        else 
        {
            return false;
        }
    }

    function getMessage($key)
    {
    	$message = Setting::where('key', '=', $key)->first()??null;

    	return $message;
    }

    function getRequiredProduct($id_promo, $source='promo_campaign'){
    	if ($source == 'deals') {
    		$promo = Deal::where('id_deals','=',$id_promo)
	    			->with([
						'deals_product_discount.product' => function($q) {
							$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
						},
						'deals_buyxgety_product_requirement.product' => function($q) {
							$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
						},
						'deals_tier_discount_product.product' => function($q) {
							$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
						},
						'deals_product_discount_rules',
						'deals_tier_discount_rules',
						'deals_buyxgety_rules'
					])
	                ->first();
    	}elseif($source == 'promo_campaign'){
	    	$promo = PromoCampaign::where('id_promo_campaign','=',$id_promo)
	    			->with([
						'promo_campaign_product_discount.product' => function($q) {
							$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
						},
						'promo_campaign_buyxgety_product_requirement.product' => function($q) {
							$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
						},
						'promo_campaign_tier_discount_product.product' => function($q) {
							$q->select('id_product', 'id_product_category', 'product_code', 'product_name');
						},
						'promo_campaign_product_discount_rules',
						'promo_campaign_tier_discount_rules',
						'promo_campaign_buyxgety_rules'
					])
	                ->first();
    	}

        if ($promo) {
        	$promo = $promo->toArray();
        	if ( ($promo[$source.'_product_discount_rules']['is_all_product']??false) == 1) 
	        {
	        	$product = '*';
	        }
	        elseif ( !empty($promo[$source.'_product_discount']) )
	        {
	        	$product = $promo[$source.'_product_discount'][0]['product']??'';
	        }
	        elseif ( !empty($promo[$source.'_tier_discount_product']) )
	        {
	        	$product = $promo[$source.'_tier_discount_product']['product']??'';
	        }
	        elseif ( !empty($promo[$source.'_buyxgety_product_requirement']) )
	        {
	        	$product = $promo[$source.'_buyxgety_product_requirement']['product']??'';
	        }
	        else
	        {
	        	$product = null;
	        }
	        return $product;
        }else{
        	return 'empty';
        }
    }

    function getAllModifier($array_modifier, $id_outlet)
    {
    	$mod = ProductModifier::select('product_modifiers.id_product_modifier','text','product_modifier_stock_status','product_modifier_price')
                // produk modifier yang tersedia di outlet
                ->join('product_modifier_prices','product_modifiers.id_product_modifier','=','product_modifier_prices.id_product_modifier')
                ->where('product_modifier_prices.id_outlet',$id_outlet)
                // produk aktif
                ->where('product_modifier_status','Active')
                // product visible
                ->where(function($query){
                    $query->where('product_modifier_prices.product_modifier_visibility','=','Visible')
                    ->orWhere(function($q){
                        $q->whereNull('product_modifier_prices.product_modifier_visibility')
                        ->where('product_modifiers.product_modifier_visibility', 'Visible');
                    });
                })
                ->whereIn('product_modifiers.id_product_modifier',$array_modifier)
                ->groupBy('product_modifiers.id_product_modifier')
                // product modifier dengan id
                ->get();
        if ($mod) {
        	return $mod;
        }else{
        	return [];
        }

    }

    /**
     * Create referal promo code 
     * @param  Integer $id_user user id of user
     * @return boolean       true if success
     */
    public static function createReferralCode($id_user) {
    	//check user have referral code
    	$check = UserReferralCode::where('id_user',$id_user)->first();
    	if($check){
    		return $check;
    	}
    	$max_iterate = 1000;
    	$iterate = 0;
    	$exist = true;
    	do{
    		$promo_code = MyHelper::createrandom(6, 'PromoCode');
    		$exist = PromoCampaignPromoCode::where('promo_code',$promo_code)->exists();
    		if($exist){$promo_code=false;};
    		$iterate++;
    	}while($exist&&$iterate<=$max_iterate);
    	if(!$promo_code){
    		return false;
    	}
    	$create = PromoCampaignPromoCode::create([
    		'id_promo_campaign' => 1,
    		'promo_code' => $promo_code
    	]);
    	if(!$create){
    		return false;
    	}
    	$create2 = UserReferralCode::create([
    		'id_promo_campaign_promo_code' => $create->id_promo_campaign_promo_code,
    		'id_user' => $id_user
    	]);
    	return $create2;
    }
    /**
     * Apply cashback to referrer
     * @param  Transaction $transaction Transaction model
     * @return boolean 
     */
    public static function applyReferrerCashback($transaction)
    {
    	if(!$transaction['id_promo_campaign_promo_code']){
    		return true;
    	}
    	$transaction->load('promo_campaign_promo_code','promo_campaign_promo_code.promo_campaign');
    	$use_referral = ($transaction['promo_campaign_promo_code']['promo_campaign']['promo_type']??false) === 'Referral';
        // apply cashback to referrer
        if ($use_referral){
            $referral_rule = PromoCampaignReferral::where('id_promo_campaign',$transaction['promo_campaign_promo_code']['id_promo_campaign'])->first();
            $referrer = UserReferralCode::where('id_promo_campaign_promo_code',$transaction['id_promo_campaign_promo_code'])->pluck('id_user')->first();
            if(!$referrer || !$referral_rule){
            	return false;
            }
            $referrer_cashback = 0;
            if($referral_rule->referrer_promo_unit == 'Percent'){
                $referrer_discount_percent = $referral_rule->referrer_promo_value<=100?$referral_rule->referrer_promo_value:100;
                $referrer_cashback = $transaction['transaction_grandtotal']*$referrer_discount_percent/100;
            }else{
                if($transaction['transaction_grandtotal'] >= $referral_rule->referred_min_value){
                    $referrer_cashback = $referral_rule->referrer_promo_value<=$transaction['transaction_grandtotal']?$referral_rule->referrer_promo_value:$transaction['transaction_grandtotal'];
                }
            }
            if($referrer_cashback){
                $insertDataLogCash = app("Modules\Balance\Http\Controllers\BalanceController")->addLogBalance( $referrer, $referrer_cashback, $transaction['id_transaction'], 'Referral Bonus', $transaction['transaction_grandtotal']);
                if (!$insertDataLogCash) {
                    return false;
                }
            }
        }
        return true;
    }

}
?>