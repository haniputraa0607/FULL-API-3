<?php

namespace Modules\PromoCampaign\Lib;

use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReport;
use Modules\PromoCampaign\Entities\UserReferralCode;
use App\Http\Models\Product;
use App\Http\Models\UserDevice;
use App\Http\Models\User;
use App\Http\Models\Transaction;
use App\Http\Models\Setting;

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
	public function validatePromo($id_promo, $id_outlet, $trxs, &$errors){
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
		$promo=PromoCampaign::with('promo_campaign_outlets')->find($id_promo);
		

		if(!$promo){
			$errors[]='Promo Campaign not found';
			return false;
		}

		$outlet = $this->checkOutletRule($id_outlet, $promo->is_all_outlet, $promo->promo_campaign_outlets);

		if(!$outlet){
			$errors[]='Promo Campaign cannot be used at this outlet';
			return false;
		}

		if(strtotime($promo->date_start)>time()||strtotime($promo->date_end)<time()){
			$errors[]='Promo campaign not valid';
			return false;
		}
		
		$discount=0;
		// add product discount if exist
		foreach ($trxs as  $id_trx => &$trx) {
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
			$product_discount=$this->getProductDiscount($product)*$trx['qty'];
			$product_price=$product->product_prices[0]->product_price??[];
			// $discount+=$product_discount;
			if($product_discount){
				// $trx['discount']=$product_discount;
				$trx['new_price']=($product_price*$trx['qty'])-$product_discount;
			}
		}

		switch ($promo->promo_type) {
			case 'Product discount':
				// load required relationship
				$promo->load('promo_campaign_product_discount','promo_campaign_product_discount_rules');
				$promo_rules=$promo->promo_campaign_product_discount_rules;
				$max_product = $promo_rules->max_product;
				$qty_promo_available = [];

				if(!$promo_rules->is_all_product){
					$promo_product=$promo->promo_campaign_product_discount->toArray();
				}else{
					$promo_product="*";
				}

				// sum total quantity of same product, if greater than max product assign value to max product
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
				}

				// check qty item get promo
				foreach ($trxs as $key => $value) {
					if (($item_get_promo[$value['id_product']] - $value['qty']) > 0) {
						$trxs[$key]['promo_qty'] = $value['qty'];
						$item_get_promo[$value['id_product']] -= $value['qty'];
					}else{
						$trxs[$key]['promo_qty'] = $item_get_promo[$value['id_product']];
						$item_get_promo[$value['id_product']] = 0;
					}
				}

				foreach ($trxs as  $id_trx => &$trx) {

					// continue if qty promo for same product is all used 
					if ($trx['promo_qty'] == 0) {
						continue;
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
						$discount+=$this->discount_product($product,$promo_rules,$trx);
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
							$discount+=$this->discount_product($product,$promo_rules,$trx);
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
				$promo->load('promo_campaign_tier_discount_rules','promo_campaign_tier_discount_product');
				$promo_product=$promo->promo_campaign_tier_discount_product;
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
				$promo_rules=$promo->promo_campaign_tier_discount_rules;
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
					if($trx['id_product']==$promo_product->id_product){
						$trx['promo_qty'] = $trx['qty'];
						$discount+=$this->discount_product($promo_product->product,$promo_rule,$trx);
					}
				}

				break;

			case 'Buy X Get Y':
				// load requirement relationship
				$promo->load('promo_campaign_buyxgety_rules','promo_campaign_buyxgety_product_requirement');
				$promo_product=$promo->promo_campaign_buyxgety_product_requirement;
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

				$promo_rules=$promo->promo_campaign_buyxgety_rules;
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
				$promo_rules=$promo->promo_campaign_buyxgety_rules;
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
				$benefit_value=$promo_rule->discount_nominal??$promo_rule->discount_percent;
				$benefit_type = $promo_rule->discount_nominal?'Nominal':'Percent';
				
				if(!$benefit_product){
					$errors[]="Product benefit not found.";
					return false;
				}
				$benefit=null;

				$rule=(object) [
					'max_qty'=>$benefit_qty,
					'discount_type'=>$benefit_type,
					'discount_value'=>$benefit_value
				];

				// add product benefit
				$benefit_item = [
					'id_custom' 	=> isset(end($trxs)['id_custom']) ? end($trxs)['id_custom']+1 : '',
					'id_product'	=> $benefit_product->id_product,
					'id_brand'		=> $benefit_product->brands[0]->id_brand??'',
					'qty'			=> $promo_rule->benefit_qty,
					'is_promo'		=> 1,
					'is_free'		=> ($promo_rule->discount_percent == 100) ? 1 : 0,
					'modifiers'		=> []
				];
				// $benefit_item['id_product']	= $benefit_product->id_product;
				// $benefit_item['id_brand'] 	= $benefit_product->brands[0]->id_brand??'';
				// $benefit_item['qty'] 		= $promo_rule->benefit_qty;
				
				$discount+=$this->discount_product($benefit_product,$rule,$benefit_item);

				// return $benefit_item;
				array_push($trxs, $benefit_item);
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
						'discount_value'=>$promo_rules->referred_promo_value
					];
					foreach ($trxs as  $id_trx => &$trx) {
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
						$discount += $this->discount_product($product,$rule,$trx);
					}
				}
		}
		// discount?
		if($discount<=0){
			$errors[]='Does not get any discount';
			return false;
		}
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
	protected function discount_product($product,$promo_rules,&$trx){
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
		$product_price=$product->product_prices[0]->product_price??[];
		if(isset($trx['new_price'])&&$trx['new_price']){
			$product_price=$trx['new_price']/$trx['qty'];
		}
		if($promo_rules->discount_type=='Nominal'){
			$discount=$promo_rules->discount_value*$discount_qty;
			$trx['discount']=($trx['discount']??0)+$discount;
			$trx['new_price']=($product_price*$trx['qty'])-$trx['discount'];
			$trx['is_promo']=1;
		}else{
			// percent
			$discount=(int)((($promo_rules->discount_value/100)*$product_price)*$discount_qty);
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
		if($promo->promo_type == 'Referral'){
			if(User::find($id_user)->transaction_online){
	        	$errors[]='Promo code not found 1';
				return false;
			}
			if(UserReferralCode::where([
				'id_promo_campaign_promo_code'=>$id_code,
				'id_user'=>$id_user
			])->exists()){
	        	$errors[]='Promo code not found 2';
	    		return false;
			}
	        $referer = UserReferralCode::where('id_promo_campaign_promo_code',$id_code)
	            ->join('users','users.id','=','user_referral_codes.id_user')
	            ->where('users.is_suspended','=',0)
	            ->first();
	        if(!$referer){
	        	$errors[] = 'Promo code not found 3';
	        }
		}

		if(!$promo){
        	$errors[]='Promo campaign not found';
    		return false;
		}
		if(!$promo->step_complete || !$promo->user_type){
        	$errors[]='Promo campaign not finished';
    		return false;
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

    function getRequiredProduct($id_promo_campaign){
    	$promo = PromoCampaign::where('id_promo_campaign','=',$id_promo_campaign)
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
        if ($promo) {
        	$promo = $promo->toArray();
        	if ( ($promo['promo_campaign_product_discount_rules']['is_all_product']??false) == 1) 
	        {
	        	$product = '*';
	        }
	        elseif ( !empty($promo['promo_campaign_product_discount']) )
	        {
	        	$product = $promo['promo_campaign_product_discount'][0]['product']??'';
	        }
	        elseif ( !empty($promo['promo_campaign_tier_discount_product']) )
	        {
	        	$product = $promo['promo_campaign_tier_discount_product']['product']??'';
	        }
	        elseif ( !empty($promo['promo_campaign_buyxgety_product_requirement']) )
	        {
	        	$product = $promo['promo_campaign_buyxgety_product_requirement']['product']??'';
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
    /**
     * Create referal promo code 
     * @param  Integer $id_user user id of user
     * @return boolean       true if success
     */
    public static function createReferralCode($id_user) {
    	//check user have referral code
    	$check = UserReferralCode::where('id_user',$id_user);
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
}
?>