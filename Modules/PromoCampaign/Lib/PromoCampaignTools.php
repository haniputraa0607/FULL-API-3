<?php

namespace Modules\PromoCampaign\Lib;

use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;
use Modules\PromoCampaign\Entities\PromoCampaignReferral;
use App\Http\Models\Product;
use App\Http\Models\ProductModifier;
use App\Http\Models\UserDevice;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\Setting;
use App\Http\Models\Deal;
use App\Http\Models\DealsUser;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;

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
	public function validatePromo($id_promo, $id_outlet, $trxs, &$errors, $source='promo_campaign', &$errorProduct=0, $delivery_fee=0){
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

		$outlet = $this->checkOutletRule($id_outlet, $promo->is_all_outlet??0, $promo_outlet, $promo->id_brand);

		if(!$outlet){
			$errors[]='Promo cannot be used at this outlet';
			return false;
		}

		if( (!empty($promo->date_start) && !empty($promo->date_end)) && (strtotime($promo->date_start)>time()||strtotime($promo->date_end)<time())){
			$errors[]='Promo is not valid';
			return false;
		}

		$discount = 0;
		$discount_delivery = 0;

		/*
		* dikomen karena sekarang belum digunakan
		* 
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
		*/

		if ($promo->promo_type != 'Discount delivery') {
			//get all modifier in array
			$mod = [];
			foreach ($trxs as $key => $value) {
				foreach ($value['modifiers'] as $key2 => $value2) {
					$mod[] = $value2['id_product_modifier']??$value2;
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
						if ( ($item_get_promo[$value['id_product']] + $value['qty']) >= $max_product && !empty($max_product)) {
							$item_get_promo[$value['id_product']] = $max_product;
						}else{
							$item_get_promo[$value['id_product']] += $value['qty'];
						}
					}
					else
					{
						if ($value['qty'] >= $max_product && !empty($max_product)) {
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
						$mod_price_qty_per_item[$value['id_product']][$key]['price'] += ($mod_price[$value2['id_product_modifier']??$value2]??0);
						$mod_price_per_item[$value['id_product']][$key] += ($mod_price[$value2['id_product_modifier']??$value2]??0);
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

					// check product brand
					if ($promo->id_brand != $trx['id_brand']) {
						continue;
					}

					$modifier = 0;
					foreach ($trx['modifiers'] as $key2 => $value2) 
					{
						$modifier += $mod_price[$value2['id_product_modifier']??$value2]??0;
					}

					// is all product get promo
					if($promo_rules->is_all_product){
						// get product data
						$product = $this->getProductPrice($id_outlet, $trx['id_product']);
						
						//is product available
						if(!$product){
							// product not available
							// $errors[]='Product with id '.$trx['id_product'].' could not be found';
							$errors[]='Produk tidak ditemukan';
							continue;
						}
						// add discount
						$discount+=$this->discount_product($product,$promo_rules,$trx, $modifier);
					}else{
						// is product available in promo
						if(is_array($promo_product)&&in_array($trx['id_product'],array_column($promo_product,'id_product'))){
							// get product data
							$product = $this->getProductPrice($id_outlet, $trx['id_product']);

							//is product available
							if(!$product){
								// product not available
								$errors[]='Produk tidak ditemukan';
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
					$errorProduct = 1;
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
				$min_qty = null;
				$max_qty = null;
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
					$errorProduct = 1;
					return false;
				}
				//get cart's product to apply promo
				$product=null;
				foreach ($trxs as &$trx) {

					// check product brand
					if ($promo->id_brand != $trx['id_brand']) {
						continue;
					}
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
					$errorProduct = 1;
					return false;
				}
				
				$product_price = $this->getProductPrice($id_outlet, $promo_product->id_product);
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
					// if($rule->max_qty<$item_get_promo[$promo_product->id_product]){
					// 	continue;
					// }
					$promo_rule=$rule;
				}
				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = 1;
					return false;
				}
				// count discount
				foreach ($trxs as $key => &$trx) {

					$modifier = 0;
					foreach ($trx['modifiers'] as $key2 => $value2) 
					{
						$modifier += $mod_price[$value2['id_product_modifier']??$value2]??0;
					}

					if($trx['id_product']==$promo_product->id_product){
						$trx['promo_qty'] = $max_qty < $trx['qty'] ? $max_qty : $trx['qty'];
						$discount+=$this->discount_product($product_price,$promo_rule,$trx, $modifier);
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
				$min_qty=null;
				$max_qty=null;
				// get min max for error message
				foreach ($promo_rules as $rule) {

					if($min_qty===null||$rule->min_qty_requirement<$min_qty){
						$min_qty=$rule->min_qty_requirement;
					}
					if($max_qty===null||$rule->max_qty_requirement>$max_qty){
						$max_qty=$rule->max_qty_requirement;
					}
				}

				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = 1;
					return false;
				}
				//get cart's product to get benefit
				$product=null;
				foreach ($trxs as &$trx) {

					// check product brand
					if ($promo->id_brand != $trx['id_brand']) {
						continue;
					}
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
					$errorProduct = 1;
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
					// if($max_req<$item_get_promo[$promo_product->id_product]){
					// 	continue;
					// }
					$promo_rule=$rule;
				}

				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$promo_product->product->product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = 1;
					return false;
				}
				$benefit_product = $this->getOneProduct($id_outlet, $promo_rule->benefit_id_product,1);
				$benefit_product_price = $this->getProductPrice($id_outlet, $promo_rule->benefit_id_product);

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
					'id_brand'		=> $promo->id_brand??'',
					'qty'			=> $promo_rule->benefit_qty,
					'is_promo'		=> 1,
					'is_free'		=> ($promo_rule->discount_type == "percent" && $promo_rule->discount_value == 100) ? 1 : 0,
					'modifiers'		=> [],
					'bonus'			=> 1
				];
				// $benefit_item['id_product']	= $benefit_product->id_product;
				// $benefit_item['id_brand'] 	= $benefit_product->brands[0]->id_brand??'';
				// $benefit_item['qty'] 		= $promo_rule->benefit_qty;

				$discount+=$this->discount_product($benefit_product_price,$rule,$benefit_item);

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
							// $errors[]='Product with id '.$trx['id_product'].' could not be found';
							$errors[]='Produk tidak ditemukan';
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
				break;

			case 'Discount bill':
				// load required relationship
				$promo->load($source.'_discount_bill_rules');
				$promo_rules = $promo[$source.'_discount_bill_rules'];
				// get jumlah harga
				$total_price=0;
				foreach ($trxs as  $id_trx => &$trx) {
					$product = $this->getProductPrice($id_outlet, $trx['id_product']);
					$price = $trx['qty'] * $product['product_price']??0;
					$total_price += $price;
				}
				if($promo_rules->discount_type == 'Percent'){
					$discount += ($total_price * $promo_rules->discount_value)/100;
					if(!empty($promo_rules->max_percent_discount) && $discount > $promo_rules->max_percent_discount){
						$discount = $promo_rules->max_percent_discount;
					}
				}else{
					if($promo_rules->discount_value < $total_price){
						$discount += $promo_rules->discount_value;
					}else{
						$discount += $total_price;
					}
				}
				break;

			case 'Discount delivery':
				// load required relationship
				$promo->load($source.'_discount_delivery_rules');
				$promo_rules = $promo[$source.'_discount_delivery_rules'];

				if ($promo_rules) {
					$discount_delivery = $this->discountDelivery(
						$delivery_fee, 
						$promo_rules->discount_type,
						$promo_rules->discount_value,
						$promo_rules->max_percent_discount
					);
				}

				break;
		}
		// discount?
		// if($discount<=0){
		// 	$errors[]='Does not get any discount';
		// 	return false;
		// }
		return [
			'item'		=> $trxs,
			'discount'	=> $discount,
			'promo_type'=> $promo->promo_type,
			'discount_delivery'	=> $discount_delivery??0
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

		$product_price = ($product['product_price']??$product->product_prices[0]->product_price??null) + $modifier;

		if(isset($trx['new_price'])&&$trx['new_price']){
			$product_price=$trx['new_price']/$trx['qty'];
		}
		if($promo_rules->discount_type=='Nominal' || $promo_rules->discount_type=='nominal'){
			$discount = $promo_rules->discount_value*$discount_qty;

			$trx['discount']		= ($trx['discount']??0)+$discount;
			$trx['new_price']		= ($product_price*$trx['qty'])-$trx['discount'];
			$trx['is_promo']		= 1;
			$trx['base_discount']	= $promo_rules->discount_value;
			$trx['qty_discount']	= $discount_qty;
		}else{
			// percent
			$discount_per_product = ($promo_rules->discount_value/100)*$product_price;
			if ($discount_per_product > $promo_rules->max_percent_discount && !empty($promo_rules->max_percent_discount)) {
				$discount_per_product = $promo_rules->max_percent_discount;
			}
			$discount=(int)($discount_per_product*$discount_qty);
			$trx['discount']		= ($trx['discount']??0)+$discount;
			$trx['new_price']		= ($product_price*$trx['qty'])-$trx['discount'];
			$trx['is_promo']		= 1;
			$trx['base_discount']	= $discount_per_product;
			$trx['qty_discount']	= $discount_qty;
		}
		if($trx['new_price']<0){
			$trx['is_promo']		= 1;
			$trx['new_price']		= 0;
			$trx['discount']		= $product_price*$discount_qty;
			$trx['base_discount']	= $product_price;
			$trx['qty_discount']	= $discount_qty;
			$discount 				= $trx['discount']-$old;
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
        if( $countSemen > ($productItem['product_prices'][0]['product_price']??[]) ){
        	$countSemen = $productItem['product_prices'][0]['product_price']??[];
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

    function checkOutletRule($id_outlet, $all_outlet, $outlet = [], $id_brand = null, $brand=[])
    {
    	if (isset($id_brand)) {
    		if (!empty($brand)) {
    			$check_brand = array_search($id_brand, array_column($brand, 'id_brand'));
                if($check_brand === false){
	    			return false;
                }
    		}
    		else{
	    		$check_brand = Outlet::where('id_outlet',$id_outlet)
	    						->whereHas('brands', function($q) use ($id_brand){
	    							$q->where('brand_outlet.id_brand', $id_brand);
	    						})
	    						->first();
	    		if (!$check_brand) {
	    			return false;
	    		}
    		}


    	}
        if ($all_outlet == '1') 
        {
            return true;
        } 
        else 
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
	        	$product = null;
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

	        if (!empty($product)) {
	        	$product['id_brand'] = $promo['id_brand']??'';
	        }
	        return $product;
        }else{
        	return null;
        }
    }

    function getAllModifier($array_modifier, $id_outlet)
    {
    	$different_price = Outlet::select('outlet_different_price')->where('id_outlet',$id_outlet)->pluck('outlet_different_price')->first();

        $mod = ProductModifier::select('product_modifiers.id_product_modifier','text','product_modifier_stock_status','product_modifier_price')
            ->whereIn('product_modifiers.id_product_modifier',$array_modifier)
            ->leftJoin('product_modifier_details', function($join) use ($id_outlet) {
                $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                    ->where('product_modifier_details.id_outlet',$id_outlet);
            })
            ->where(function($q){
                $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
            })
            ->where(function($q){
                $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
            })
            ->where(function($query){
                $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                        ->orWhere(function($q){
                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                        });
            });

        if($different_price){
            $mod->join('product_modifier_prices',function($join) use ($id_outlet){
                $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                $join->where('product_modifier_prices.id_outlet',$id_outlet);
            });

        }else{
            $mod->join('product_modifier_global_prices',function($join) use ($id_outlet){
                $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
            });
        }

        $mod = $mod->get();
        if ($mod) {
        	return $mod;
        }else{
        	return [];
        }
    }

    public function getOneProduct($id_outlet, $id_product, $brand=null)
    {
	    $product = Product::where('id_product',$id_product)
			        ->whereHas('brand_category')
			        ->whereRaw('products.id_product in (CASE
			                    WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
			                    is NULL THEN products.id_product
			                    ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
			                END)');

		if (!empty($brand)) {

			$product = $product->with('brands');
		}

		$product = $product->first();

		return $product;
    }

    public function getProductPrice($id_outlet, $id_product, $brand=null)
    {
	    $different_price = Outlet::select('outlet_different_price')->where('id_outlet',$id_outlet)->pluck('outlet_different_price')->first();
        // $productPrice = ProductPrice::where(['id_product' => $valueData['id_product'], 'id_outlet' => $data['id_outlet']])->first();
        if($different_price){
            $productPrice = ProductSpecialPrice::where(['id_product' => $id_product, 'id_outlet' => $id_outlet])->first()->toArray();
            if($productPrice){
                $productPrice['product_price'] = $productPrice['product_special_price'];
            }
        }else{
            $productPrice = ProductGlobalPrice::where(['id_product' => $id_product])->first()->toArray();
            if($productPrice){
                $productPrice['product_price'] = $productPrice['product_global_price'];
            }
        }

		return $productPrice;
    }

    /**
     * Create referal promo code 
     * @param  Integer $id_user user id of user
     * @return boolean       true if success
     */
    public static function createReferralCode($id_user) {
    	//check user have referral code
    	$referral_campaign = PromoCampaign::select('id_promo_campaign')->where('promo_type','referral')->first();
    	if(!$referral_campaign){
    		return false;
    	}
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
    		'id_promo_campaign' => $referral_campaign->id_promo_campaign,
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
                PromoCampaignReferralTransaction::where('id_transaction',$transaction['id_transaction'])->update(['referrer_bonus'=>$referrer_cashback]);
	            $referrer_total_cashback = UserReferralCode::where('id_user',$referrer)->first();
	            if($referrer_total_cashback){
	            	$upData = [
	            		'cashback_earned'=>$referrer_total_cashback->cashback_earned+$referrer_cashback,
	            		'number_transaction'=>$referrer_total_cashback->number_transaction+1
	            	];
	            	if(!$referrer_total_cashback->referral_code){
	            		$upData['referral_code'] = PromoCampaignPromoCode::select('promo_code')->where('id_promo_campaign_promo_code',$transaction['id_promo_campaign_promo_code'])->pluck('promo_code')->first();
	            	}
	            	$up = $referrer_total_cashback->update($upData);
	            }else{
	            	$up = UserReferralCode::create([
	            		'id_user' => $referrer,
	            		'referral_code' => PromoCampaignPromoCode::select('promo_code')->where('id_promo_campaign_promo_code',$transaction['id_promo_campaign_promo_code'])->pluck('promo_code')->first(),
	            		'number_transaction' => 1,
	            		'cashback_earned' => $referrer_cashback
	            	]);
	            }
	            if(!$up){
	            	return false;
	            }
            }
        }
        return true;
    }

    public function removeBonusItem($item)
    {
    	foreach ($item as $key => $value) 
		{
			if (!empty($value['bonus'])) {
				unset($item[$key]);
				break;
			}
		}

		return $item;
    }

    public function discountDelivery($delivery_fee, $discount_type, $discount_value, $discount_max)
    {
    	$discount = 0;
    	if($discount_type == 'Percent'){
			$discount = ($delivery_fee * $discount_value)/100;
			if(!empty($discount_max) && $discount > $discount_max){
				$discount = $discount_max;
			}
		}else{
			if($discount_value < $delivery_fee){
				$discount = $discount_value;
			}else{
				$discount = $delivery_fee;
			}
		}

		return $discount;
    }

    public function checkPaymentRule($all_payment, $payment_method, $promo_payment_list)
    {
    	if (!is_array($promo_payment_list)) {
    		$promo_payment_list = $promo_payment_list->toArray();
    	}

    	if ($all_payment) {
    		return true;
    	}

    	if (in_array($payment_method, $promo_payment_list)) {
    		return true;
    	}else{
    		return false;
    	}	
    }

    public function checkShipmentRule($all_shipment, $shipment_method, $promo_shipment_list)
    {
    	if (!is_array($promo_shipment_list)) {
    		$promo_shipment_list = $promo_shipment_list->toArray();
    	}

    	if ($all_shipment) {
    		return true;
    	}

    	if (in_array($shipment_method, $promo_shipment_list)) {
    		return true;
    	}else{
    		return false;
    	}
    }
}
?>