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
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\BrandProduct;
use Modules\Brand\Entities\Brand;

use App\Lib\MyHelper;
use Modules\IPay88\Lib\IPay88;
use Modules\PromoCampaign\Lib\PromoCampaignToolsV1;

class PromoCampaignTools{

    function __construct()
    {
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
    }
	/**
	 * validate transaction to use promo campaign
	 * @param  	int 		$id_promo 	id promo campaigm
	 * @param  	array 		$trxs      	array of item and total transaction
	 * @param  	array 		$error     	error message
	 * @return 	array/boolean     modified array of trxs if can, otherwise false
	 */
	public function validatePromo($request, $id_promo, $id_outlet, $trxs, &$errors, $source='promo_campaign', &$errorProduct=0, $delivery_fee=0){
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

		if ($promo->id_brand) {
			$pct = new PromoCampaignToolsV1();
			return $pct->validatePromo($id_promo, $id_outlet, $trxs, $errors, $source, $errorProduct, $delivery_fee);
		}

		$promo_brand = $promo->{$source.'_brands'}->pluck('id_brand')->toArray();
		// $outlet = $this->checkOutletRule($id_outlet, $promo->is_all_outlet??0, $promo_outlet, $promo->id_brand);
		$outlet = $this->checkOutletBrandRule($id_outlet, $promo->is_all_outlet??0, $promo_outlet, $promo_brand, $promo->brand_rule);

		if(!$outlet){
			$errors[]='Promo tidak dapat digunakan di outlet ini.';
			return false;
		}

		if (isset($request['type'])) {
			$promo_shipment = $promo->{$source.'_shipment_method'}->pluck('shipment_method');

			$check_shipment = $this->checkShipmentRule($promo->is_all_shipment??0, $request->type, $promo_shipment);
			if(!$check_shipment){
				// $errors[]='Promo cannot be used for this shipment method';
				$errors[]='Promo tidak dapat digunakan untuk tipe order ini';
				return false;
			}
		}

		if (isset($request['payment_type']) && (isset($request['payment_id']) || isset($request['payment_detail'])) ) {
			$promo_payment 	= $promo->{$source.'_payment_method'}->pluck('payment_method');
			$payment_method = $this->getPaymentMethod($request['payment_type'], $request['payment_id'], $request['payment_detail']);
			$check_payment 	= $this->checkPaymentRule($promo->is_all_payment??0, $payment_method, $promo_payment);

			if(!$check_payment){
				// $errors[]='Promo cannot be used for this payment method';
				$errors[]='Promo tidak dapat digunakan untuk metode pembayaran ini';
				return false;
			}
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

				if ($promo->product_rule === 'and') {
					$product_name = 'semua product bertanda khusus';
				}else {
					$product_name = 'product bertanda khusus';
				}

				if(!$promo_rules->is_all_product){
					if ($promo[$source.'_product_discount']->isEmpty()) {
						$errors[]='Produk tidak ditemukan';
						return false;
					}
					$promo_product = $promo[$source.'_product_discount']->toArray();
					$promo_product_count = count($promo_product);

					if ($promo_product_count == 1) {
						$product_error_applied = 1;
					}else{
						$product_error_applied = 'all';
					}

					$check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

					// promo product not available in cart?
					if (!$check_product) {
						$message = $this->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
						$message = MyHelper::simpleReplace($message,['product'=>$product_name]);
						$errors[]= $message;
						$errorProduct = $product_error_applied;
						return false;
					}
				}else{
					$promo_product = "*";
					$product_error_applied = 'all';
				}

				$get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
				$product = $get_promo_product['product'];

				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$message = $this->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name]);

					$errors[] = $message;
					$errorProduct = $product_error_applied;
					return false;
				}

				// get product price
				foreach ($product as $key => $value) {
					$product[$key]['price'] = null;
					$product[$key]['product_price'] = null;
					$product_price = $this->getProductPrice($id_outlet, $value['id_product'], $value['id_product_variant_group']);
					if(!$product_price){
						$errors[]='Produk tidak ditemukan';
						continue;
					}
					$product[$key]['product_price'] = $product_price;
					$product[$key]['price'] = $product_price['product_price'];
				}

				// sort product by price asc
				uasort($product, function($a, $b){
					return $a['price'] - $b['price'];
				});

				$merge_product = [];
				foreach ($product as $key => $value) {
					if (isset($merge_product[$value['id_product']])) {
						$merge_product[$value['id_product']] += $value['qty'];
					}
					else {
						$merge_product[$value['id_product']] = $value['qty'];
					}
				}

				if ($promo->product_rule == 'and') {
					$max_promo_qty = 0;
					foreach ($merge_product as $value) {
						if ($max_promo_qty == 0 || $max_promo_qty > $value) {
							$max_promo_qty = $value;
						}
					}
					$promo_qty_each = $max_promo_qty == 0 || (isset($promo_rules->max_product) && $promo_rules->max_product < $max_promo_qty) ? $promo_rules->max_product : $max_promo_qty;
				}else{
					$promo_qty_each = $promo_rules->max_product;
				}

				// get max qty of product that can get promo
				foreach ($product as $key => $value) {

					if (!empty($promo_qty_each)) {
						if (!isset($qty_each[$value['id_brand']][$value['id_product']])) {
							$qty_each[$value['id_brand']][$value['id_product']] = $promo_qty_each;
						}

						if ($qty_each[$value['id_brand']][$value['id_product']] < 0) {
							$qty_each[$value['id_brand']][$value['id_product']] = 0;
						}

						if ($qty_each[$value['id_brand']][$value['id_product']] > $value['qty']) {
							$promo_qty = $value['qty'];
						}else{
							$promo_qty = $qty_each[$value['id_brand']][$value['id_product']];
						}

						$qty_each[$value['id_brand']][$value['id_product']] -= $value['qty'];
						
					}else{
						$promo_qty = $value['qty'];
					}

					$product[$key]['promo_qty'] = $promo_qty;
				}

				foreach ($trxs as $key => &$trx) {
					if (!isset($product[$key])) {
						continue;
					}

					$modifier = 0;
					foreach ($trx['modifiers'] as $key2 => $value2) 
					{
						$modifier += $mod_price[$value2['id_product_modifier']??$value2]??0;
					}

					$trx['promo_qty'] = $product[$key]['promo_qty'];
					$discount += $this->discount_product($product[$key]['product_price'],$promo_rules,$trx, $modifier);
				}
				if($discount<=0){
					$message = $this->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>'product bertanda khusus']);

					$errors[]= $message;
					$errorProduct = $product_error_applied;
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

				$minmax = $min_qty != $max_qty ? "$min_qty - $max_qty" : $min_qty;
				$promo_product_array = $promo_product->toArray();
				$promo_product_id = array_column($promo_product_array, 'id_product');
				$promo_product_count = count($promo_product);

				if ($promo_product_count == 1) {
					$product_error_applied = 1;
				}else{
					$product_error_applied = 'all';
				}

				$check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

				// promo product not available in cart?
				if ($promo->product_rule === 'and') {
					$product_name = 'semua product bertanda khusus';
				}else {
					$product_name = 'product bertanda khusus';
				}

				if (!$check_product) {
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);
					$errors[]= $message;
					$errorProduct = $product_error_applied;
					return false;		
				}

				$get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
				$product = $get_promo_product['product'];
				$total_product = $get_promo_product['total_product'];

				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = $product_error_applied;
					return false;
				}

				// sum total quantity of same product
				$item_get_promo = []; // include brand
				$item_promo = []; // only product/item
				foreach ($product as $key => $value) 
				{
					if (isset($item_promo[$value['id_product']])) {
						$item_promo[$value['id_product']] += $value['qty'];
					}
					else{
						$item_promo[$value['id_product']] = $value['qty'];
					}

					if (isset($item_get_promo[$value['id_brand'].'-'.$value['id_product']])) {
						$item_get_promo[$value['id_brand'].'-'.$value['id_product']] += $value['qty'];
					}
					else{
						$item_get_promo[$value['id_brand'].'-'.$value['id_product']] = $value['qty'];
					}
				}

				//find promo rules
				$promo_rule = null;
				if ($promo->product_rule == "and") {
					$req_valid 	= true;
					$rule_key	= [];
					$promo_qty_each = 0;
					foreach ($product as $key => &$val) {
						$min_qty 	= null;
						$max_qty 	= null;
						$temp_rule_key[$key] = [];

						foreach ($promo_rules as $key2 => $rule) {
							if($min_qty === null || $rule->min_qty < $min_qty){
								$min_qty = $rule->min_qty;
							}
							if($max_qty === null || $rule->max_qty > $max_qty){
								$max_qty = $rule->max_qty;
							}
							
							if($rule->min_qty > $item_get_promo[$val['id_brand'].'-'.$val['id_product']]){
								if (empty($temp_rule_key[$key])) {
									$req_valid = false;
									break;
								}else{
									continue;
								}
							}
							$temp_rule_key[$key][] 	= $key2;
						}

						if ($item_get_promo[$val['id_brand'].'-'.$val['id_product']] < $promo_qty_each || $promo_qty_each == 0) {
							$promo_qty_each = $item_get_promo[$val['id_brand'].'-'.$val['id_product']];
						}

						if (!empty($rule_key)) {
							$rule_key = array_intersect($rule_key, $temp_rule_key[$key]);
						}else{
							$rule_key = $temp_rule_key[$key];
						}

						if (!$req_valid) {
							break;
						}
					}

					if ($req_valid && !empty($rule_key)) {
						$rule_key 	= end($rule_key);
						$promo_rule = $promo_rules[$rule_key];
						$promo_qty_each = $promo_qty_each > $promo_rule->max_qty ? $promo_rule->max_qty : $promo_qty_each;
					}
				}
				else{
					$min_qty 	= null;
					$max_qty 	= null;

					foreach ($promo_rules as $rule) {
						if($min_qty === null || $rule->min_qty < $min_qty){
							$min_qty = $rule->min_qty;
						}
						if($max_qty === null || $rule->max_qty > $max_qty){
							$max_qty = $rule->max_qty;
						}
						
						if($rule->min_qty > $total_product){ // total keseluruhan product
							continue;
						}
						$promo_rule = $rule;
					}
				}

				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_tier_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = $product_error_applied;
					return false;
				}

				// get product price
				foreach ($product as $key => $value) {
					$product[$key]['price'] = null;
					$product[$key]['product_price'] = null;
					$product_price = $this->getProductPrice($id_outlet, $value['id_product'], $value['id_product_variant_group']);
					if(!$product_price){
						$errors[]='Produk tidak ditemukan';
						continue;
					}
					$product[$key]['product_price'] = $product_price;
					$product[$key]['price'] = $product_price['product_price'];
				}

				// sort product price asc
				uasort($product, function($a, $b){
					return $a['price'] - $b['price'];
				});

				// get max qty of product that can get promo
				$total_promo_qty = $promo_rule->max_qty < $total_product ? $promo_rule->max_qty : $total_product;
				foreach ($product as $key => $value) {

					if (!empty($promo_qty_each)) {
						if (!isset($qty_each[$value['id_brand']][$value['id_product']])) {
							$qty_each[$value['id_brand']][$value['id_product']] = $promo_qty_each;
						}

						if ($qty_each[$value['id_brand']][$value['id_product']] < 0) {
							$qty_each[$value['id_brand']][$value['id_product']] = 0;
						}

						if ($qty_each[$value['id_brand']][$value['id_product']] > $value['qty']) {
							$promo_qty = $value['qty'];
						}else{
							$promo_qty = $qty_each[$value['id_brand']][$value['id_product']];
						}

						$qty_each[$value['id_brand']][$value['id_product']] -= $value['qty'];
						
					}else{
						if ($total_promo_qty < 0) {
							$total_promo_qty = 0;
						}

						if ($total_promo_qty > $value['qty']) {
							$promo_qty = $value['qty'];
						}else{
							$promo_qty = $total_promo_qty;
						}

						$total_promo_qty -= $promo_qty;
					}

					$product[$key]['promo_qty'] = $promo_qty;
				}

				// count discount
				$product_id = array_column($product, 'id_product');
				foreach ($trxs as $key => &$trx) {

					if (!isset($product[$key])) {
						continue;
					}

					if (!in_array($trx['id_brand'], $promo_brand)) {
						continue;
					}

					$modifier = 0;
					foreach ($trx['modifiers'] as $key2 => $value2) 
					{
						$modifier += $mod_price[$value2['id_product_modifier']??$value2]??0;
					}

					if(in_array($trx['id_product'], $product_id)){
						// add discount
						$trx['promo_qty'] = $product[$key]['promo_qty'];
						$discount += $this->discount_product($product[$key]['product_price'],$promo_rule,$trx, $modifier);
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

				$promo_product_count = count($promo_product);

				if ($promo_product_count == 1) {
					$product_error_applied = 1;
				}else{
					$product_error_applied = 'all';
				}

				// sum total quantity of same product
				foreach ($trxs as $key => $value) 
				{
					if (isset($item_get_promo[$value['id_brand']][$value['id_product']])) 
					{
						$item_get_promo[$value['id_brand']][$value['id_product']] += $value['qty'];
					}
					else
					{
						$item_get_promo[$value['id_brand']][$value['id_product']] = $value['qty'];
					}
				}

				$promo_rules=$promo[$source.'_buyxgety_rules'];

				// get min max for error message
				$min_qty = null;
				$max_qty = null;
				foreach ($promo_rules as $rule) {

					if($min_qty===null||$rule->min_qty_requirement<$min_qty){
						$min_qty=$rule->min_qty_requirement;
					}
					if($max_qty===null||$rule->max_qty_requirement>$max_qty){
						$max_qty=$rule->max_qty_requirement;
					}
				}

				// promo product not available in cart?
				$minmax = $min_qty != $max_qty ? "$min_qty - $max_qty" : $min_qty;
				$promo_product_array = $promo_product->toArray();
				$promo_product_id = array_column($promo_product_array, 'id_product');

				$check_product = $this->checkProductRule($promo, $promo_brand, $promo_product, $trxs);

				// promo product not available in cart?
				if ($promo->product_rule === 'and') {
					$product_name = 'semua product bertanda khusus';
				}else {
					$product_name = 'product bertanda khusus';
				}

				if (!$check_product) {
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);
					
					$errors[]= $message;
					$errorProduct = $product_error_applied;
					return false;		
				}

				$get_promo_product = $this->getPromoProduct($trxs, $promo_brand, $promo_product);
				$product = $get_promo_product['product'];
				$total_product = $get_promo_product['total_product'];

				// product not found? buat jaga-jaga kalau sesuatu yang tidak diinginkan terjadi
				if(!$product){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = $product_error_applied;
					return false;
				}

				// sum total quantity of same product
				$item_get_promo = []; // include brand
				$item_promo = []; // only product
				foreach ($product as $key => $value) 
				{
					if (isset($item_promo[$value['id_product']])) {
						$item_promo[$value['id_product']] += $value['qty'];
					}
					else{
						$item_promo[$value['id_product']] = $value['qty'];
					}

					if (isset($item_get_promo[$value['id_brand'].'-'.$value['id_product']])) {
						$item_get_promo[$value['id_brand'].'-'.$value['id_product']] += $value['qty'];
					}
					else{
						$item_get_promo[$value['id_brand'].'-'.$value['id_product']] = $value['qty'];
					}
				}

				//find promo
				$promo_rules=$promo[$source.'_buyxgety_rules'];
				$promo_rule=false;
				$min_qty=null;
				$max_qty=null;

				$promo_rule = null;
				if ($promo->product_rule == "and") {
					$req_valid 	= true;
					$rule_key	= [];
					foreach ($product as $key => &$val) {
						$min_qty 	= null;
						$max_qty 	= null;
						$temp_rule_key[$key] = [];

						foreach ($promo_rules as $key2 => $rule) {
							if($min_qty === null || $rule->min_qty_requirement < $min_qty){
								$min_qty = $rule->min_qty_requirement;
							}
							if($max_qty === null || $rule->max_qty_requirement > $max_qty){
								$max_qty = $rule->max_qty_requirement;
							}
							
							if($rule->min_qty_requirement > $item_get_promo[$val['id_brand'].'-'.$val['id_product']]){
								if (empty($temp_rule_key[$key])) {
									$req_valid = false;
									break;
								}else{
									continue;
								}
							}
							$temp_rule_key[$key][] = $key2;
						}

						if (!empty($rule_key)) {
							$rule_key = array_intersect($rule_key, $temp_rule_key[$key]);
						}else{
							$rule_key = $temp_rule_key[$key];
						}

						if (!$req_valid) {
							break;
						}
					}
					if ($req_valid && !empty($rule_key)) {
						$rule_key 	= end($rule_key);
						$promo_rule = $promo_rules[$rule_key];
					}
				}
				else{
					$min_qty 	= null;
					$max_qty 	= null;

					foreach ($promo_rules as $rule) {
						if($min_qty === null || $rule->min_qty_requirement < $min_qty){
							$min_qty = $rule->min_qty_requirement;
						}
						if($max_qty === null || $rule->max_qty_requirement > $max_qty){
							$max_qty = $rule->max_qty_requirement;
						}
						
						if($rule->min_qty_requirement > $total_product){ // total keseluruhan product
							continue;
						}
						$promo_rule = $rule;
					}
				}

				if(!$promo_rule){
					$minmax=$min_qty!=$max_qty?"$min_qty - $max_qty":$min_qty;
					$message = $this->getMessage('error_buyxgety_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b> sebanyak <b>%minmax%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>$product_name, 'minmax'=>$minmax]);

					$errors[]= $message;
					$errorProduct = $product_error_applied;
					return false;
				}
				// get product with brand
				$benefit_product = $this->getOneProduct($id_outlet, $promo_rule->benefit_id_product, $promo_rule->id_brand, 'with_brand');

				if(!$benefit_product){
					$errors[]="Product benefit not found.";
					return false;
				}

				$benefit_qty	= $promo_rule->benefit_qty;
				$benefit_value 	= $promo_rule->discount_value;
				$benefit_type 	= $promo_rule->discount_type;
				$benefit_max_value = $promo_rule->max_percent_discount;
				$benefit_product_price = $this->getProductPrice($id_outlet, $promo_rule->benefit_id_product, $promo_rule->id_product_variant_group, $promo_rule->id_brand);

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
					'id_brand'		=> $benefit_product->brand->id_brand,
					'qty'			=> $promo_rule->benefit_qty,
					'is_promo'		=> 1,
					'is_free'		=> ($promo_rule->discount_type == "percent" && $promo_rule->discount_value == 100) ? 1 : 0,
					'modifiers'		=> [],
					'bonus'			=> 1,
					'id_product_variant_group' => $promo_rule->id_product_variant_group
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
				$promo_brand_flipped = array_flip($promo_brand);
				// get jumlah harga
				$total_price=0;
				foreach ($trxs as  $id_trx => &$trx) {
					if (!isset($promo_brand_flipped[$trx['id_brand']])) {
						continue;
					}
					$product = $this->getProductPrice($id_outlet, $trx['id_product'], $trx['id_product_variant_group']);
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

				if($discount<=0){
					$message = $this->getMessage('error_product_discount')['value_text']??'Promo hanya akan berlaku jika anda membeli <b>%product%</b>.'; 
					$message = MyHelper::simpleReplace($message,['product'=>'product bertanda khusus']);

					$errors[]= $message;
					$errorProduct = 'all';
					return false;
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
	public function discount_product($product,$promo_rules,&$trx, $modifier=null){
		// check discount type
		$discount 	= 0;
		$modifier 	= 0; // reset all modifier price to 0
		// set quantity of product to apply discount
		$discount_qty = $trx['promo_qty']??$trx['qty'];
		$old = $trx['discount']??0;
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
			$product_price_total = $product_price * $discount_qty;
			if ($discount > $product_price_total) {
				$discount = $product_price_total;
			}
			$trx['discount']		= ($trx['discount']??0)+$discount;
			$trx['new_price']		= ($product_price*$trx['qty'])-$trx['discount'];
			$trx['is_promo']		= 1;
			$trx['base_discount']	= $product_price < $promo_rules->discount_value ? $product_price : $promo_rules->discount_value;
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

    public function checkOutletBrandRule($id_outlet, $all_outlet, $promo_outlets, $promo_brands, $brand_rule = 'and')
    {
    	$outlet_brands 	= BrandOutlet::where('id_outlet', $id_outlet)->pluck('id_brand')->toArray();
    	$check_brand 	= array_diff($promo_brands, $outlet_brands);

    	if ($brand_rule == 'or') {
    		if (count($check_brand) == count($promo_brands)) {
	    		return false;
	    	}
    	}else{
	    	if (!empty($check_brand)) {
    			return false;
    		}
    	}

        if ($all_outlet == '1') 
        {
            return true;
        } 
        else 
        {
            foreach ($promo_outlets as $value) 
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
		        if (!empty($promo[$source.'_product_discount'][0]['id_brand'])) {
		        	$product['id_brand'] = $promo[$source.'_product_discount'][0]['id_brand'];
		        }
	        }
	        elseif ( !empty($promo[$source.'_tier_discount_product']) )
	        {
	        	$product = $promo[$source.'_tier_discount_product']['product']??$promo[$source.'_tier_discount_product'][0]['product']??'';
	        	if (!empty($promo[$source.'_tier_discount_product'][0]['id_brand'])) {
		        	$product['id_brand'] = $promo[$source.'_tier_discount_product'][0]['id_brand'];
		        }
	        }
	        elseif ( !empty($promo[$source.'_buyxgety_product_requirement']) )
	        {
	        	$product = $promo[$source.'_buyxgety_product_requirement']['product']??$promo[$source.'_buyxgety_product_requirement'][0]['product']??'';
	        	if (!empty($promo[$source.'_buyxgety_product_requirement'][0]['id_brand'])) {
		        	$product['id_brand'] = $promo[$source.'_buyxgety_product_requirement'][0]['id_brand'];
		        }
	        }
	        else
	        {
	        	$product = null;
	        }

	        if (!empty($product) && !empty($promo['id_brand'])) {
	        	$product['id_brand'] = $promo['id_brand'];
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

    public function getOneProduct($id_outlet, $id_product, $id_brand, $brand=null)
    {
	    $product = Product::where('id_product',$id_product)
			        ->whereHas('brand_category', function($q) use ($id_brand){
			        	$q->where('id_brand', $id_brand);
			        })
			        ->whereRaw('products.id_product in 
			        (CASE
	                    WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
	                    is NULL THEN products.id_product
	                    ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
	                END)')
			        ->first();

		if ($product && !empty($brand)) {

			$product_brand = Brand::join('brand_product', 'brand_product.id_brand', '=', 'brands.id_brand')
							->where('brand_active', '1')
							->where('id_product', $id_product)
							->first();
			if (!$product_brand) {
				$product = false;
			}else{
				$product->brand = $product_brand;
			}
		}

		return $product;
    }

    public function getProductPrice($id_outlet, $id_product, $id_product_variant_group=null, $id_brand=null)
    {
	    $different_price = Outlet::select('outlet_different_price')->where('id_outlet',$id_outlet)->pluck('outlet_different_price')->first();

        if ($id_brand) {
		    $check_brand = BrandProduct::where('id_brand', $id_brand)->where('id_product', $id_product)->first();
		    if (!$check_brand) {
		    	return false;
		    }
        }

        if ($id_product_variant_group) {
        	if($different_price){
        		$productPrice = ProductVariantGroupSpecialPrice::select('product_variant_group_price')->where('id_product_variant_group', $id_product_variant_group)->first();

	            if($productPrice){
	                $productPrice['product_price'] = $productPrice['product_variant_group_price'];
	            }
	        }else{
	        	$productPrice = ProductVariantGroup::select('product_variant_group_price')->where('id_product_variant_group', $id_product_variant_group)->first();

	            if($productPrice){
	                $productPrice['product_price'] = $productPrice['product_variant_group_price'];
	            }
	        }
        }else{
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

    public function getPaymentMethod($payment_type, $payment_id, $payment_detail)
    {
    	// payment_id for ipay88
    	// payment_detail for midtrans
    	$payment_method = null;
    	if ( $payment_type == "Ipay88" ) {
	    	$payment_method = $this->getPaymentIpay88($payment_id);
    	}
    	elseif ( $payment_type == "Midtrans" ) {
	    	$payment_method =  $payment_detail;
    	}
    	
    	return $payment_method;
    }

    public function getPaymentIpay88($payment_id)
    {
    	$payment_id = strtoupper($payment_id);
    	$ipay88 = new Ipay88;
	    $payment_list = $ipay88->payment_id;
	    $payment_list['CREDIT_CARD'] = 'Credit Card';
	    $payment_list['CREDIT CARD'] = 'Credit Card';
	    $payment_list['OVO'] = 'Ovo';

	    if (isset($payment_list[$payment_id])) {
	    	$payment_method = $payment_list[$payment_id];
	    }else{
	    	$payment_method = null;
	    }
	    
	    return $payment_method;
    }

    public function checkProductRule($promo, $promo_brand, $promo_product, $trxs)
    {
    	if (!is_array($promo_product)) {
    		$promo_product_array = $promo_product->toArray();
    	}else{
    		$promo_product_array = $promo_product;
    	}
    	$promo_product_id = array_column($promo_product_array, 'id_product');
    	// merge total quantity of same product
		$merge_product = [];
		foreach ($trxs as $key => $value) {
			if (isset($merge_product[$value['id_brand']][$value['id_product']])) {
				$merge_product[$value['id_brand']][$value['id_product']] += $value['qty'];
			}
			else {
				$merge_product[$value['id_brand']][$value['id_product']] = $value['qty'];
			}
		}

		// check merged product with rule brand and rule product
		$check_product = [];				
		foreach ($merge_product as $key => $val) { // key = id_brand
			if (!in_array($key, $promo_brand)) {
				continue;
			}

			foreach ($val as $key2 => $val2) { // key2 = id_product, val2 = qty
				$found = false;
				foreach ($promo_product_array as $key3 => $value3) { // check product & brand requirement
					if ($value3['id_brand'] == $key && $value3['id_product'] == $key2) {
						$found = true;
					}
				}

				if($found && in_array($key2, $promo_product_id)){
					$check_product[$key.'-'.$key2] = $key;
				}
			}
		}

		// promo product not available in cart?
		if ($promo->product_rule === 'and') {
			if (count($check_product) != count($promo_product)) {
				return false;		
			}
		}elseif($promo->product_rule === 'or') {
			if (empty($check_product)) {
				return false;
			}
		}

		return true;
    }

    public function getPromoProduct(&$trxs, $promo_brand, $promo_product)
    {
    	if ($promo_product != '*') {
	    	if (!is_array($promo_product)) {
	    		$promo_product_array = $promo_product->toArray();
	    	}else{
	    		$promo_product_array = $promo_product;
	    	}
    	}

		$product = [];
		$total_product = 0;
		foreach ($trxs as $key => &$trx) {

			if (!in_array($trx['id_brand'], $promo_brand)) {
				continue;
			}

			if (isset($promo_product_array)) {
				foreach ($promo_product_array as $key2 => $value2) {
					if ($value2['id_brand'] == $trx['id_brand'] && $value2['id_product'] == $trx['id_product']) {
						$product[$key] = $trx;
						$total_product += $trx['qty'];
						$trx['is_promo'] = 1;
						break;
					}
				}
			}else{
				$product[$key] = $trx;
				$total_product += $trx['qty'];
			}
		}

	    return [
	    	'product' => $product,
	    	'total_product' => $total_product
	    ];
    }

    public function getCheapestVariant($id_outlet, $id_product)
    {
	    $outlet = Outlet::select('id_outlet', 'outlet_different_price')->where('id_outlet',$id_outlet)->first();
	    $variant_list = Product::getVariantTree($id_product, $outlet);
	    $result = null;

	    if ($variant_list) {
	    	$variant = 	$this->getVariant($variant_list['base_price'], $variant_list['variants_tree']['childs'], $group_price);

	    	if (isset($variant['id_product_variant_group'])) {
	    		$result = $variant['id_product_variant_group'];
	    	}
	    }

		return $result;
    }

    public function getVariant($base_price, $variant, &$group_price)
    {
    	try {
	    	foreach ($variant as $key => $value) {

	    		if (isset($value['variant']['childs'])) {
					$group_price = self::getVariant($base_price, $value['variant']['childs'], $group_price);
	    		}

				if (isset($value['product_variant_group_price'])) {
					if ($value['product_variant_group_price'] == $base_price) {
			    		$group_price = [
			    			'price' => $value['product_variant_group_price'],
			    			'id_product_variant_group' => $value['id_product_variant_group']
			    		];
			    		break;
					}
				}
	    	}

	    	return $group_price;
    		
    	} catch (\Exception $e) {
    		return $e->getMessage();
    	}
    }

    public function applyPromoProduct($post, $brand_products, &$promo_error)
    {
    	$result = $brand_products;

        // set default flag to 0
        foreach ($result as $id_brand => $categories) {
        	foreach ($categories as $id_category => $products) {
        		foreach ($products['list']??$products as $key => $value) {
        			if (!is_numeric($id_category)) {
        				$result[$id_brand][$id_category]['list'][$key]['is_promo'] = 0;
        			}else{
        				$result[$id_brand][$id_category][$key]['is_promo'] = 0;
        			}
        		}
        	}
        }

        // return data if not using promo
        if ((empty($post['promo_code']) && empty($post['id_deals_user']) && empty($post['id_subscription_user']))) {
        	return $result;
        }
        $promo_error = null;
        if ((!empty($post['promo_code']) && !empty($post['id_deals_user']) && !empty($post['id_subscription_user'])) 
            || (!empty($post['promo_code']) && !empty($post['id_deals_user']) && empty($post['id_subscription_user'])) 
            || (!empty($post['promo_code']) && empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
            || (empty($post['promo_code']) && !empty($post['id_deals_user']) && !empty($post['id_subscription_user']))
        ) {
        	$promo_error = 'Promo not valid';
        	return $result;
        }

        if (!empty($post['promo_code'])) {
            $code = app($this->promo_campaign)->checkPromoCode($post['promo_code'], 1, 1);
            if (!$code) {
                $promo_error = 'Promo not valid';
                return $result;
            }
            $source 		= 'promo_campaign';
            $brands 		= $code->promo_campaign->promo_campaign_brands()->pluck('id_brand')->toArray();
    		$all_outlet 	= $code['promo_campaign']['is_all_outlet']??0;
    		$promo_outlet 	= $code['promo_campaign']['promo_campaign_outlets']??[];
    		$id_brand_promo	= $code['promo_campaign']['id_brand']??null;
    		$brand_rule		= $code['promo_campaign']['brand_rule']??'and';

    		// if promo doesn't have product related rule, return data
    		if ($code->promo_type != 'Product discount' && $code->promo_type != 'Tier discount' && $code->promo_type != 'Buy X Get Y' && $code->promo_type != 'Discount bill') {
                return $result;
    		}

        } elseif (!empty($post['id_deals_user'])) {
            $code = app($this->promo_campaign)->checkVoucher($post['id_deals_user'], 1, 1);
            if (!$code) {
                $promo_error = 'Promo not valid';
                return $result;
            }
            $source 		= 'deals';
    		$brands 		= $code->dealVoucher->deals->deals_brands()->pluck('id_brand')->toArray();
    		$all_outlet 	= $code['dealVoucher']['deals']['is_all_outlet']??0;
    		$promo_outlet 	= $code['dealVoucher']['deals']['outlets_active']??[];
    		$id_brand_promo = $code['dealVoucher']['deals']['id_brand']??null;
    		$brand_rule		= $code['dealVoucher']['deals']['brand_rule']??'and';

    		// if promo doesn't have product related rule, return data
    		if ($code->dealVoucher->deals->promo_type != 'Product discount' 
    			&& $code->dealVoucher->deals->promo_type != 'Tier discount' 
    			&& $code->dealVoucher->deals->promo_type != 'Buy X Get Y'
    			&& $code->dealVoucher->deals->promo_type != 'Discount bill'
    		) {
                return $result;
    		}

        } elseif (!empty($post['id_subscription_user'])) {
            $code = app($this->subscription_use)->checkSubscription($post['id_subscription_user'], 1, 1, 1);
            if (!$code) {
                $promo_error = 'Promo not valid';
                return $result;
            }
            $source 		= 'subscription';
            $brands 		= $code->subscription_user->subscription->subscription_brands->pluck('id_brand')->toArray();
    		$all_outlet 	= $code['subscription_user']['subscription']['is_all_outlet']??0;
    		$promo_outlet 	= $code['subscription_user']['subscription']['outlets_active']??[];
    		$id_brand_promo	= $code['subscription_user']['subscription']['id_brand']??null;
    		$brand_rule		= $code['subscription_user']['subscription']['brand_rule']??'and';
        }

        if (($code['promo_campaign']['date_end'] ?? $code['voucher_expired_at'] ?? $code['subscription_expired_at']) < date('Y-m-d H:i:s')) {
            $promo_error = 'Promo is ended';
            return $result;
        }

        $code = $code->toArray();

        if (!empty($id_brand_promo)) {
			$check_outlet = $this->checkOutletRule($post['id_outlet'], $all_outlet, $promo_outlet, $id_brand_promo);
		}else{
			$check_outlet = $this->checkOutletBrandRule($post['id_outlet'], $all_outlet, $promo_outlet, $brands, $brand_rule);
		}

		if (!$check_outlet) {
			$promo_error = 'Promo tidak dapat digunakan di outlet ini.';
            return $result;
		}

        $applied_product = app($this->promo_campaign)->getProduct($source, ($code['promo_campaign'] ?? $code['deal_voucher']['deals'] ?? $code['subscription_user']['subscription']))['applied_product'] ?? [];

        if (!empty($id_brand_promo)) { // single brand
        	foreach ($result as $id_brand => $categories) {
				foreach ($categories as $id_category => $products) {
					foreach ($products['list']??$products as $key => $product) {
						if ($product['id_brand'] != $id_brand_promo){
							continue;
						}
						if ($applied_product == '*') { // all product
							if (!is_numeric($id_category)) {
		        				$result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
		        			}else{
		        				$result[$id_brand][$id_category][$key]['is_promo'] = 1;
		        			}
						}else{
							if (isset($applied_product['id_product'])) { // single product
								if ($applied_product['id_product'] == $product['id_product']) {
									if (!is_numeric($id_category)) {
				        				$result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
				        			}else{
				        				$result[$id_brand][$id_category][$key]['is_promo'] = 1;
				        			}
								}
							}else{ // multiple product
								foreach ($applied_product as $val) {
									if ($val['id_product'] == $product['id_product']) {
										if (!is_numeric($id_category)) {
					        				$result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
					        			}else{
					        				$result[$id_brand][$id_category][$key]['is_promo'] = 1;
					        			}
									}
								}
							}
						}
					}
				}
			}	
        }else{ // multi brand
			foreach ($result as $id_brand => $categories) {
				foreach ($categories as $id_category => $products) {
					foreach ($products['list']??$products as $key => $product) {
						if ($applied_product == '*') { // all product
							if (in_array($product['id_brand'], $brands)) {
								if (!is_numeric($id_category)) {
			        				$result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
			        			}else{
			        				$result[$id_brand][$id_category][$key]['is_promo'] = 1;
			        			}
							}
						}else{
							foreach ($applied_product as $val) { // multiple product
								if ($val['id_brand'] == $product['id_brand'] && $val['id_product'] == $product['id_product']) {
									if (!is_numeric($id_category)) {
				        				$result[$id_brand][$id_category]['list'][$key]['is_promo'] = 1;
				        			}else{
				        				$result[$id_brand][$id_category][$key]['is_promo'] = 1;
				        			}
								}
							}
						}
					}
				}
			}
        }

        return $result;
    }
}
?>