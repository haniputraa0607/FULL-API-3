<?php

namespace Modules\PromoCampaign\Lib;

use App\Http\Models\PromoCampaign;
use App\Http\Models\Product;
use App\Http\Models\UserDevice;
use App\Http\Models\PromoCampaignReport;

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
	public function validatePromo($id_promo,$trxs,&$errors){
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
		$promo=PromoCampaign::with(['promo_codes'])->find($id_promo);
		if($promo->promo_codes){
			if(strtotime($promo->promo_codes->date_start)>time()||strtotime($promo->promo_codes->date_end)<time()){
				$errors[]='Promo campaign tidak berlaku';
				return false;
			}
		}
		if(!$promo){
			$errors[]='Promo Campaign not found';
			return false;
		}
		$discount=0;
		// add product discount if exist
		foreach ($trxs as  $id_trx => &$trx) {
			$product=Product::find($trx['id_product']);
			$product_discount=$this->getProductDiscount($product)*$trx['qty'];
			$product_price=$product->product_price;
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
				if(!$promo_rules->is_all_product){
					$promo_product=$promo->promo_campaign_product_discount->toArray();
				}else{
					$promo_product="*";
				}
				foreach ($trxs as  $id_trx => &$trx) {
					// is all product get promo
					if($promo_rules->is_all_product){
						// get product data
						$product=Product::find($trx['id_product']);
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
							$product=Product::find($trx['id_product']);
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
				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$errors[]='Anda harus menambahkan '.$promo_product->product->product_name.' untuk menggunakan promo ini.';
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
					$errors[]='Anda harus menambahkan '.$promo_product->product->product_name.' untuk menggunakan promo ini.';
					return false;
				}
				//find promo
				$promo_rules=$promo->promo_campaign_tier_discount_rules;
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
					if($rule->min_qty>$product['qty']){
						continue;
					}
					$promo_rule=$rule;
				}
				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$errors[]="Anda harus menambahkan $minmax {$promo_product->product->product_name} untuk menggunakan promo ini.";
					return false;
				}
				// count discount
				$discount+=$this->discount_product($promo_product->product,$promo_rule,$product);
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
				// promo product not available in cart?
				if(!in_array($promo_product->id_product, array_column($trxs, 'id_product'))){
					$errors[]='Anda harus menambahkan '.$promo_product->product->product_name.' untuk menggunakan promo ini.';
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
					$errors[]='Anda harus menambahkan '.$promo_product->product->product_name.' untuk menggunakan promo ini.';
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
					$sama = $promo_product->id_product==$rule->benefit_id_product;
					if($sama){
						$min_req=$rule->min_qty_requirement+$benefit_qty;
						$max_req=$rule->max_qty_requirement+$benefit_qty;
					}else{
						$min_req=$rule->min_qty_requirement;
						$max_req=$rule->max_qty_requirement;
					}
					if($min_qty===null||$rule->min_qty_requirement<$min_qty){
						$min_qty=$min_req;
					}
					if($max_qty===null||$rule->max_qty_requirement>$max_qty){
						$max_qty=$max_req;
					}
					if($min_req>$product['qty']){
						continue;
					}
					$promo_rule=$rule;
				}
				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$errors[]="Anda harus menambahkan $minmax {$promo_product->product->product_name} untuk menggunakan promo ini.";
					return false;
				}
				$benefit_product=Product::find($promo_rule->benefit_id_product);
				$benefit_qty=$promo_rule->benefit_qty;
				$benefit_value=$promo_rule->discount_percent;
				$sama = $promo_product->id_product==$benefit_product->id_product;
				if(!$benefit_product){
					$errors[]="Anda harus menambahkan {$benefit_qty} {$benefit_product->product_name} untuk menggunakan promo ini.";
					return false;
				}
				$benefit=null;
				//get cart's product to apply promo
				foreach ($trxs as &$trx) {
					//is this the cart product we looking for?
					if($trx['id_product']==$benefit_product->id_product){
						//set reference to this cart product
						$benefit=&$trx;
						// break from loop
						break;
					}
				}

				if(!$benefit||($benefit['qty']??0)<$benefit_qty){
					$errors[]="Anda harus menambahkan {$benefit_qty} {$benefit_product->product_name} untuk menggunakan promo ini.";
					return false;
				}
				$rule=(object) [
					'max_qty'=>$benefit_qty,
					'discount_type'=>'Percent',
					'discount_value'=>$benefit_value
				];
				$discount+=$this->discount_product($benefit_product,$rule,$benefit);
				break;

			case 'Discount global':
				// load required relationship
				$promo->load('promo_campaign_discount_global_rule');
				$promo_rules=$promo->promo_campaign_discount_global_rule;
				// get jumlah harga
				$total_price=0;
				foreach ($trxs as  $id_trx => &$trx) {
					$product=Product::find($trx['id_product']);
					$qty=$trx['qty'];
					$total_price+=$qty*$product->product_price;
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
		$product_price=$product->product_price;
		if(isset($trx['new_price'])&&$trx['new_price']){
			$product_price=$trx['new_price']/$trx['qty'];
		}
		if($promo_rules->discount_type=='Nominal'){
			$discount=$promo_rules->discount_value*$discount_qty;
			$trx['discount']=($trx['discount']??0)+$discount;
			$trx['new_price']=($product_price*$trx['qty'])-$trx['discount'];
		}else{
			// percent
			$discount=(int)((($promo_rules->discount_value/100)*$product_price)*$discount_qty);
			$trx['discount']=($trx['discount']??0)+$discount;
			$trx['new_price']=($product_price*$trx['qty'])-$trx['discount'];
		}
		if($trx['new_price']<0){
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
	public function validateUser($id_promo,$id_user,&$errors=[]){
		$promo=PromoCampaign::with('promo_campaign_rules_view','promo_codes')->find($id_promo);
		if(!$promo){
        	$errors[]='Promo campaign not found';
    		return false;
		}
		if($promo->promo_campaign_rules_view){
	        $conds=json_decode($promo->promo_campaign_rules_view->promo_campaign_rules,true);
		}else{
        	$errors[]='Promo campaign not finished';
    		return false;
		}
        // create id condition
        $conds['id']=[
        	['=',$id_user]
        ];
        $user=app($this->user)->UserFilter($promo->campaign_rule,$conds)['result']??false;
        if(!$user){
        	$errors[]='User not found';
    		return false;
        }
        // use promo code?
        if($promo->promo_codes&&$promo->promo_codes->limitation_usage){
        	$device=UserDevice::where('id_user',$id_user)->orderBy('updated_at','desc')->first();
        	// limit usage user?
        	if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('id_user',$id_user)->count()>=$promo->promo_codes->limitation_usage){
	        	$errors[]='Kuota anda untuk penggunaan kode promo ini telah habis';
	    		return false;
        	}
        	if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('device_id',$device->device_id)->count()>=$promo->promo_codes->limitation_usage){
	        	$errors[]='Kuota device anda untuk penggunaan kode promo ini telah habis';
	    		return false;
        	}
        }else{
        	$promo->load('vouchers');
        	if($promo->vouchers->voucher_limit){
	        	$device=UserDevice::where('id_user',$id_user)->orderBy('updated_at','desc')->first();
	        	// limit usage user?
	        	if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('id_user',$id_user)->count()>=$promo->vouchers->voucher_limit){
		        	$errors[]='Kuota anda untuk penggunaan voucher ini telah habis';
		    		return false;
	        	}
	        	if(PromoCampaignReport::where('id_promo_campaign',$id_promo)->where('device_id',$device->device_id)->count()>=$promo->vouchers->voucher_limit){
		        	$errors[]='Kuota device anda untuk penggunaan voucher ini telah habis';
		    		return false;
	        	}
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

                    $count = $productItem['product_price'] * $jat / 100;
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
        if($countSemen>$productItem['product_price']){
        	$countSemen=$productItem['product_price'];
        }
        return $countSemen;
    }
}
?>