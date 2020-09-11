<?php

namespace Modules\RedirectComplex\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

use Modules\RedirectComplex\Entities\RedirectComplexReference;
use Modules\RedirectComplex\Entities\RedirectComplexProduct;
use Modules\RedirectComplex\Entities\RedirectComplexOutlet;
use Modules\RedirectComplex\Entities\RedirectComplexBrand;

use Modules\RedirectComplex\Http\Requests\CreateRequest;
use Modules\RedirectComplex\Http\Requests\EditRequest;
use Modules\RedirectComplex\Http\Requests\UpdateRequest;
use Modules\RedirectComplex\Http\Requests\DeleteRequest;
use Modules\RedirectComplex\Http\Requests\DetailRequest;

use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;

use Modules\ProductVariant\Entities\ProductGroup;
use Modules\ProductVariant\Entities\ProductVariant;

use App\Http\Models\Outlet;
use App\Http\Models\Setting;
use App\Http\Models\Product;

use Modules\Brand\Entities\Brand;

use App\Lib\MyHelper;
use DB;
use Modules\PromoCampaign\Lib\PromoCampaignTools;

class ApiRedirectComplex extends Controller
{

	function __construct()
	{
        date_default_timezone_set('Asia/Jakarta');
		$this->outlet 				= "Modules\Outlet\Http\Controllers\ApiOutletController";
		$this->online_transaction   = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
		$this->api_promo   			= "Modules\PromoCampaign\Http\Controllers\ApiPromo";
		$this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
	}

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
    	$query 	= RedirectComplexReference::orderBy('updated_at','Desc');
    	$data 	= $query->paginate(10)->toArray();
    	$data['data'] = $query->paginate(10)
        				->each(function($q){
						    $q->setAppends([
						        'get_promo'
						    ]);
						})
	        			->toArray();


    	return MyHelper::checkGet($data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(CreateRequest $request)
    {
    	$post 		= $request->json()->all();
    	$status		= false;
    	$reference 	= [
    		'type'				=> 'push',
			'name'				=> $request->name,
			'outlet_type'		=> $request->outlet_type,
			'promo_type'		=> !empty($request->promo) ? 'promo_campaign' : null,
			'promo_reference'	=> !empty($request->promo) ? $request->promo : null,
			'payment_method'	=> !empty($request->payment) && !empty($request->use_product) && !empty($request->product) ? $request->payment : null,
			'use_product'		=> !empty($request->use_product) && !empty($request->product) ? $request->use_product : 0,
			'transaction_type'	=> !empty($request->transaction_type) && !empty($request->use_product) && !empty($request->product) ? $request->transaction_type : null
    	];

    	DB::beginTransaction();

    	do {

	    	$save_reference = RedirectComplexReference::create($reference);
	    	if(!$save_reference) break;

	    	if ($request->brand) {
				$save_brand = $this->saveBrand($request->brand, $save_reference->id_redirect_complex_reference);
	    		if(!$save_brand) break;
	    	}

	    	if ($request->outlet  && $request->outlet_type == 'specific') {
				$save_outlet = $this->saveOutlet($request->outlet, $save_reference->id_redirect_complex_reference);
	    		if(!$save_outlet) break;
	    	}

	    	if ($request->product && $request->use_product) {
				$save_product = $this->saveProduct($request->product, $save_reference->id_redirect_complex_reference);
	    		if(!$save_product) break;
	    	}
    		
    		$status = $save_reference;
    		DB::commit();
    	} while (0);

        return MyHelper::checkCreate($status);
    }

    function saveBrand($brand=[], $id)
    {
    	$now = date("Y-m-d H:i:s");
    	$data = [];
    	foreach ($brand as $key => $value) {
    		$data[] = [
    			'id_redirect_complex_reference' => $id,
				'id_brand' 		=> $value,
				'created_at' 	=> $now,
				'updated_at'	=> $now,
                'created_by'   	=> Auth::id(),
            	'updated_by'    => Auth::id()
    		];
    	}

    	$save = RedirectComplexBrand::insert($data);

    	return $save;
    }

    function saveOutlet($outlet=[], $id)
    {
    	$now = date("Y-m-d H:i:s");
    	$data = [];
    	foreach ($outlet as $key => $value) {
    		$data[] = [
    			'id_redirect_complex_reference' => $id,
				'id_outlet' 	=> $value,
				'created_at' 	=> $now,
				'updated_at'	=> $now,
                'created_by'   	=> Auth::id(),
            	'updated_by'    => Auth::id()
    		];
    	}

    	$save = RedirectComplexOutlet::insert($data);

    	return $save;
    }

    function saveProduct($product=[], $id)
    {
    	$now = date("Y-m-d H:i:s");
    	$data = [];
    	foreach ($product as $key => $value) {
    		$explode = explode('-', $value['id']);
    		$id_brand = $explode[0];
    		$id_product = $explode[1];
    		$data[] = [
    			'id_redirect_complex_reference' => $id,
				'id_brand' 						=> $id_brand,
				'id_product' 					=> $id_product,
				'qty' 							=> $value['qty'],
				'created_at' 					=> $now,
				'updated_at'					=> $now,
                'created_by'   					=> Auth::id(),
            	'updated_by'    				=> Auth::id()
    		];
    	}

    	$save = RedirectComplexProduct::insert($data);

    	return $save;
    }

    public function edit(EditRequest $request)
    {
    	$data = RedirectComplexReference::where('id_redirect_complex_reference', $request->id_redirect_complex_reference)
    			->with([
    				'outlets' => function($q) {
    					$q->select('outlets.id_outlet', 'outlet_code', 'outlet_name');
    				},
    				'products' => function($q) {
    					$q->select('products.id_product', 'product_code', 'product_name');
    				},
    				'brands' => function($q) {
    					$q->select('brands.id_brand', 'code_brand', 'name_brand');
    				}
    			])
    			->first();
		$data = $data->append('get_promo');

    	return MyHelper::checkGet($data);
    }

    public function update(UpdateRequest $request)
    {
    	$post 		= $request->json()->all();
    	$status		= false;
    	$reference 	= [
			'type'				=> 'push',
			'name'				=> $request->name,
			'outlet_type'		=> $request->outlet_type,
			'promo_type'		=> !empty($request->promo) ? 'promo_campaign' : null,
			'promo_reference'	=> !empty($request->promo) ? $request->promo : null,
			'payment_method'	=> !empty($request->payment) && !empty($request->use_product) && !empty($request->product) ? $request->payment : null,
			'use_product'		=> !empty($request->use_product) && !empty($request->product) ? $request->use_product : 0,
			'transaction_type'	=> !empty($request->transaction_type) && !empty($request->use_product) && !empty($request->product) ? $request->transaction_type : null
    	];

    	DB::beginTransaction();

    	try {
    		do {
	    		$delete = $this->deleteRule($request->id_redirect_complex_reference);
	    		if(!$delete) break;

	    		$save_reference = RedirectComplexReference::where('id_redirect_complex_reference', $request->id_redirect_complex_reference)->update($reference);

	    		if ($request->brand) {
					$save_brand 	= $this->saveBrand($request->brand, $request->id_redirect_complex_reference);
		    		if(!$save_brand) break;
		    	}

		    	if ($request->outlet && $request->outlet_type == 'specific') {
					$save_outlet 	= $this->saveOutlet($request->outlet, $request->id_redirect_complex_reference);
					if(!$save_outlet) break;
		    	}

		    	if ($request->product && $request->use_product) {
					$save_product 	= $this->saveProduct($request->product, $request->id_redirect_complex_reference);
					if(!$save_product) break;
		    	}

	    		DB::commit();
		    	$status = true;
    		} while (0);
    	} 
    	catch (Exception $e) {
    		DB::rollback();
    		$status = false;
    	}

        return MyHelper::checkUpdate($status);
    }

    function deleteRule($id)
    {
    	try {
	    	$del = RedirectComplexProduct::where('id_redirect_complex_reference', $id)->delete();
	    	$del = RedirectComplexOutlet::where('id_redirect_complex_reference', $id)->delete();
	    	$del = RedirectComplexBrand::where('id_redirect_complex_reference', $id)->delete();
    		
    		return true;
    	} 
    	catch (Exception $e) {
    		return false;
    	}
    }

    function delete(DeleteRequest $request)
    {
        $post=$request->json()->all();
        $delete=RedirectComplexReference::where('id_redirect_complex_reference',$post['id_redirect_complex_reference'])->delete();

        return MyHelper::checkDelete($delete);
    }

    function listActive(Request $request){
        $post = $request->json()->all();

        $data = RedirectComplexReference::whereNotNull('name')
        		->whereNotNull('type');
        		// ->whereNotNull('outlet_type')
        		// ->whereHas('redirect_complex_products');

        if(isset($post['select'])){
            $data = $data->select($post['select']);
        }
        $data = $data->get();
        return response()->json(MyHelper::checkGet($data));
    }

    function detail(DetailRequest $request)
    {
    	$post = $request->all();
    	$use_promo = false;
    	$data = [
    		'outlet' 	=> null,
    		'item' 		=> null,
    		'promo'		=> null,
    		'payment' 	=> null,
    		'transaction_type' => null
    	];

    	$reference 	= RedirectComplexReference::where('id_redirect_complex_reference', $request->id_reference)
					->with([
	    				'outlets' => function($q) {
	    					$q->select('outlets.id_outlet', 'outlet_code', 'outlet_name');
	    				},
	    				'products' => function($q) {
	    					// $q->with(['brands', 'product_variants']); not using variant
	    					$q->with(['brands']);
	    				}
	    			])
					->first();

		if (!$reference) {
			return ['status' => 'fail', 'messages' => ['Reference not found']];
		}
    	$reference = $reference->append('get_promo');

		// get promo
		$promo 	= $this->getPromo($reference->promo_type, $reference->promo_reference);
		$data['promo'] = $promo;
		$use_promo = $promo['use_promo'];

	   	// get outlet
    	if (!empty($reference['outlet_type'])) {
	    	$outlet 	= $this->getOutlet(
				    		$request->latitude, 
				    		$request->longitude, 
				    		$reference->outlets, 
				    		$reference->outlet_type, 
				    		$promo
				    	);

	    	if ($outlet['status']) {
	    		// dd($outlet);
	    		$outlet 		= $outlet['outlet_promo'] ?? $outlet['outlet'];
	    		$use_promo 		= $outlet['use_promo'];
				$data['outlet'] = [
					'id_outlet' 	=> $outlet['id_outlet'],
					'outlet_code' 	=> $outlet['outlet_code']
				];
			}else{
				return ['status' => 'fail', 'messages' => ['Outlet not found']];
			}
    	}

		// get product
		if ($reference['use_product']) {
			$products = $this->getProductV2($reference->products, $outlet['id_outlet']);

			if ($products) {
				$data_trx = [
		    		'id_outlet' 	=> $outlet['id_outlet'],
		    		'device_type' 	=> $request->device_type,
		    		'device_id' 	=> $request->device_id,
		    		'promo_code' 	=> null,
		    		'id_deals_user' => null,
		    		'type'			=> null,
		    		'item' 			=> $products
		    	];

		    	if ($use_promo) {
		    		$data_trx['promo_code'] 	= $promo['promo_code'] ?? null;
		    		$data_trx['id_deals_user'] 	= $promo['id_deals_user'] ?? null;
		    	}

				$custom_request = new \Modules\Transaction\Http\Requests\CheckTransaction;
				$custom_request = $custom_request
								->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($data_trx))
								->merge($data_trx)
								->setUserResolver(function () use ($request) {
									return $request->user();
								});
				$data['item'] =  app($this->online_transaction)->checkTransaction($custom_request)['result']['item'] ?? null;

				if ($data['item'] === []) {
					$data['item'] = null;
				}

			}
			else {
				$data['item'] = null;
			}

			
			if ($use_promo && empty($online_trx['promo_error'])) {
				$use_promo = true;
			}else{
				$use_promo = false;
			}
		}

		// get payment
		if ($data['item']) {
			$payment = $this->getPayment($reference->payment_method);
			$data['payment'] = $payment;

			$data['transaction_type'] = $reference->transaction_type;
		}

		// trigger check used promo if promo valid
		if ($use_promo) {
			$data_promo = [
				'promo_code' 	=> $promo['promo_code'] ?? null,
				'id_deals_user' => $promo['id_deals_user'] ?? null,
				'device_type' 	=> $request->device_type,
				'device_id' 	=> $request->device_id
			];

			$custom_request = new \Modules\PromoCampaign\Http\Requests\ValidateCode;
			$custom_request = $custom_request
							->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($data_promo))
							->merge($data_promo)
							->setUserResolver(function () use ($request) {
								return $request->user();
							});
			$check_promo 	=  app($this->promo_campaign)->checkValid($custom_request);
			if(($check_promo['status']??true) != 'success'){
				$data['promo'] = null;
			}
		}
		else{
			$data['promo'] = null;
		}
		$data['redirect'] = $this->getRedirect($data['outlet'], $data['item'], $data['promo'], $data['payment']);

		return MyHelper::checkGet($data);
    }

    function getOutlet($latitude, $longitude, $outlet_list=[], $outlet_type=null, $promo) {

        // outlet
        $outlet = Outlet::with(['today'])->select('outlets.id_outlet','outlets.outlet_name','outlets.outlet_phone','outlets.outlet_code','outlets.outlet_status','outlets.outlet_address','outlets.id_city','outlet_latitude','outlet_longitude')->where('outlet_status', 'Active')->whereNotNull('id_city')->orderBy('outlet_name','asc');

        $outlet->whereHas('brands',function($query){
            $query->where('brand_active','1');
        });

        $outlet = $outlet->get()->toArray();

        if (!empty($outlet)) {
            $processing = '0';
            $settingTime = Setting::where('key', 'processing_time')->first();
            if($settingTime && $settingTime->value){
                $processing = $settingTime->value;
            }
            foreach ($outlet as $key => $value) {
                $jaraknya =   number_format((float)app($this->outlet)->distance($latitude, $longitude, $value['outlet_latitude'], $value['outlet_longitude'], "K"), 2, '.', '');
                settype($jaraknya, "float");

                $outlet[$key]['distance'] = number_format($jaraknya, 2, '.', ',')." km";
                $outlet[$key]['dist']     = (float) $jaraknya;

                $outlet[$key] = app($this->outlet)->setAvailableOutlet($outlet[$key], $processing);

                if(isset($outlet[$key]['today']['time_zone'])){
                    $outlet[$key]['today'] = app($this->outlet)->setTimezone($outlet[$key]['today']);
                }
            }
            usort($outlet, function($a, $b) {
                return $a['dist'] <=> $b['dist'];
            });

        }else{
            return ['status' => 'fail', 'messages' => ['There is no open store','at this moment']];
        }

        if(!$outlet){
            return ['status' => 'fail', 'messages' => ['There is no open store','at this moment']];
        }

    	if ($promo['use_promo']) {
    		$get_promo = app($this->promo_campaign)->checkPromoCode($promo['promo_code'], 'outlet');
			if ($get_promo) {
				$promo_outlet = $get_promo->promo_campaign->promo_campaign_outlets;
			}
			$pct = new PromoCampaignTools();
    	}

    	$selected_outlet = [
    		'outlet' 		=> null,
    		'outlet_promo' 	=> null
    	];

        if ($outlet_type) {
        	if ($outlet_type == 'specific') {
	    		$outlet_list = array_column($outlet_list->toArray(), 'id_outlet');
	    		if ($promo['use_promo']) {
		    		foreach ($outlet as $key => $value) {

		    			if (in_array($value['id_outlet'], $outlet_list)) {
		    				if (empty($selected_outlet['outlet'])) {
			    				$selected_outlet['outlet'] = [
			    					'use_promo'		=> false,
				    				'id_outlet' 	=> $value['id_outlet'],
				    				'outlet_code' 	=> $value['outlet_code']
				    			];
				    		}

			    			if ($promo['use_promo']) {
			    				$outlet = $pct->checkOutletRule($value['id_outlet'], $get_promo->is_all_outlet??0, $promo_outlet);
			    				if ($outlet) {
				    				$selected_outlet['outlet_promo'] = [
				    					'use_promo'		=> true,
				    					'id_outlet' 	=> $value['id_outlet'],
					    				'outlet_code' 	=> $value['outlet_code']
					    			];
				    				break;
			    				}
			    			}
			    			else{
			    				break;
			    			}
		    			}
		    		}
		    	}
		    	else {
		    		foreach ($outlet as $key => $value) {
		    			if (empty($selected_outlet['outlet']) && in_array($value['id_outlet'], $outlet_list)) {
		    				$selected_outlet['outlet'] = [
		    					'use_promo'		=> false,
			    				'id_outlet' 	=> $value['id_outlet'],
			    				'outlet_code' 	=> $value['outlet_code']
			    			];

			    			break;
		    			}
		    		}
		    	}
	    	}
	    	else {

	    		if ($promo['use_promo']) {
		    		foreach ($outlet as $key => $value) {
		    				$outlet = $pct->checkOutletRule($value['id_outlet'], $get_promo->is_all_outlet??0, $promo_outlet);
		    				$selected_outlet['outlet_promo'] = [
		    					'use_promo'		=> true,
		    					'id_outlet' 	=> $value['id_outlet'],
			    				'outlet_code' 	=> $value['outlet_code']
			    			];
		    				break;
		    		}
	    		}

	    		if (empty($selected_outlet['outlet']) && !empty($outlet[0]['id_outlet'])) {
	    			$selected_outlet['outlet'] = [
	    				'use_promo'		=> false,
	    				'id_outlet' 	=> $outlet[0]['id_outlet'],
	    				'outlet_code' 	=> $outlet[0]['outlet_code']
	    			];
	    		}
	    	}
        }

        if (!$selected_outlet['outlet'] && !$selected_outlet['outlet_promo']) {
        	return ['status' => false, 'messages' => ['Outlet not found']];
        }
        else {
        	return ['status' => true]+$selected_outlet;
        }
    }

    function getProduct($products, $id_outlet)
    {
    	$data 	= [];
    	$result = null;
    	foreach ($products as $value) {
    		$temp = [
				"id_product_group" => $value['id_product_group'],
		    	"bonus"		=> 0,
				"id_brand"	=> $value['pivot']['id_brand'],
				"modifiers"	=> [],
				"note"		=> "",
				"qty"		=> $value['pivot']['qty'],
				"variants"	=> []
			];

			foreach ($value['product_variants'] as $value2) {
				$temp['variants'][] = $value2['id_product_variant'];
			}
			$data[] = $temp;
    	}
    	if (!empty($data)) {
    		$result = $data;
    	}
    	return $result;

    }

    function getProductV2($products, $id_outlet)
    {
    	$data 	= [];
    	$result = null;
    	foreach ($products as $value) {
    		$temp = [
				"id_custom"		=> null,
	            "id_product"	=> $value['id_product'],
	            "id_brand"		=> $value['pivot']['id_brand'],
	            "qty"			=> $value['pivot']['qty'],
	            "note"			=> "",
	            "modifiers"		=> []
			];

			$data[] = $temp;
    	}

    	if (!empty($data)) {
    		$result = $data;
    	}
    	return $result;

    }
    function getPromo($promo_type, $promo_reference)
    {
    	$result = [
    		'use_promo'		=> false,
    		'promo_code' 	=> null,
    		'id_deals_user' => null
    	];

    	if ($promo_reference) {
	    	if ($promo_type == 'promo_campaign') {
	    		$promo = PromoCampaignPromoCode::where('id_promo_campaign', $promo_reference)->first();
	    		$result['use_promo'] 	= true;
	    		$result['promo_code'] 	= $promo['promo_code'] ?? null;
	    	}
    	}

    	return $result;
    }

    public function getData(Request $request)
    {
        $post = $request->json()->all();
        $data = [];
        switch ($post['get']) {
        	case 'Outlet':
            	$data = Outlet::select('id_outlet', DB::raw('CONCAT(outlet_code, " - ", outlet_name) AS outlet'));

            	if (!empty($post['brand'])) {
	                foreach ($post['brand'] as $value) {
		                $data = $data->whereHas('brands',function($query) use ($value){
				                    $query->where('brands.id_brand',$value);
				                });
		            }
	            }

            	$data = $data->get()->toArray();
        		break;
        	
        	case 'Product':
        		if ($post['type'] == 'group')
		        {
		            $data = ProductGroup::select('id_product_group as id_product', DB::raw('CONCAT(product_group_code, " - ", product_group_name) AS product'))->whereNotNull('id_product_category')->get()->toArray();
		        }
		        else
		        {
		            $data = Product::select('products.id_product', 'brands.id_brand' ,DB::raw('CONCAT(name_brand, " - ", product_code, " - ", product_name) AS product'),DB::raw('CONCAT(products.id_product, ".", brands.id_brand) AS id_product'))
		            		->whereHas('product_group', function($q) {
		            			$q->whereNotNull('id_product_category');
		            		})
		            		->leftJoin('brand_product', 'products.id_product', '=', 'brand_product.id_product')
		            		->join('brands', 'brands.id_brand', '=', 'brand_product.id_brand')
		            		->groupBy('brand_product.id_brand_product')
		            		->orderBy('brands.id_brand');

			        if (!empty($post['brand'])) {
		                $data = $data->whereIn('brands.id_brand',$post['brand']);
		            }

		            $data = $data->get()->toArray();
		        }

        		break;

        	case 'ProductGroup':
        		$data = ProductGroup::select('id_product_group', DB::raw('CONCAT(product_group_code, " - ", product_group_name) AS product_group'))->whereNotNull('id_product_category')->get()->toArray();
        		break;

        	case 'promo':
        		$now = date('Y-m-d H:i:s');
        		// return $post;
        		switch ($post['type']) {
        			case 'promo_campaign':
        				// $data = PromoCampaign::select('promo_campaigns.id_promo_campaign as id_promo', DB::raw('CONCAT(promo_promo_code, " - ", campaign_name) AS promo'), 'promo_campaigns.date_start')
        				$data = PromoCampaign::select(
        							'promo_campaigns.id_promo_campaign as id_promo', 
        							'promo_code',
        							'campaign_name', 
        							'promo_campaigns.date_start'
        						)
        						->where('code_type','Single')
        						->join('promo_campaign_promo_codes', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
        						// ->where('date_start', '<', $now) get promo that hasn't ended only
        						->where('date_end', '>', $now)
        						->where('step_complete','=',1);
					    // check outlet
					    if (isset($post['outlet'])) {
					    	$data = $data->where(function($q) use ($post) {
			            	$q->where('is_all_outlet',1);
			            		$q->orWhere(function($q2) use ($post) {
			            			foreach ($post['outlet'] as $value) {
			            				$q2->whereHas('outlets',function($q3) use ($value){
						                    $q3->where('outlets.id_outlet',$value);
						                });
			            			}
			            		});
			            	});
					    }

					   	// check brand
					   	/* commented because promo campaign doesnt have brand column
					   	if (isset($post['brand'])) {
						    $data = $data->whereIn('id_brand', $post['brand']);
					   	}
					   	*/
					   	$data = $data->get()->toArray();

					   	if (!empty($data)) {
					   		foreach ($data as $key => $value) {
					   			$status = null;
					   			$status = $value['date_start'] < $now ? 'started' : 'not started';
					   			$data[$key]['promo'] = $status.' - '.$value['promo_code'].' - '.$value['campaign_name'];
					   		}
					   	}
        				break;
        			
        			default:
        				$data = [];
        				break;
        		}
        		break;

        	case 'product-only':

				$data = Product::select('products.id_product', 'brands.id_brand' ,DB::raw('CONCAT(name_brand, " - ", product_code, " - ", product_name) AS product'),DB::raw('CONCAT(products.id_product, ".", brands.id_brand) AS id_product'))
						->leftJoin('brand_product', 'products.id_product', '=', 'brand_product.id_product')
						->join('brands', 'brands.id_brand', '=', 'brand_product.id_brand')
						->whereNotNull('brand_product.id_product_category')
						->groupBy('brand_product.id_brand_product')
						->orderBy('brands.id_brand');

				if (!empty($post['brand'])) {
					$data = $data->whereIn('brands.id_brand',$post['brand']);
				}

				$data = $data->get()->toArray();

				break;

			case 'promo-detail':

				$data = PromoCampaign::select('*')
						->where('promo_campaigns.id_promo_campaign',$post['id_promo'])
						->join('promo_campaign_promo_codes','promo_campaign_promo_codes.id_promo_campaign','=','promo_campaigns.id_promo_campaign')
						->first();

				if (!empty($data)) {
					// commented because no need to check all rules
					// $data = $data->append('get_all_rules'); 
					$promo_periode 	= date("d F Y H:i", strtotime($data['date_start'])).' - '.date("d F Y H:i", strtotime($data['date_end']));
					$promo_outlet 	= $data['is_all_outlet'] ? 'All Outlet' : 'Specific Outlet';
					$promo_total_coupon = $data['total_coupon'] ? $data['total_coupon'] : 'Unlimited';

					$result = [
						'promo_name' 	=> $data['campaign_name'],
						'promo_code' 	=> $data['promo_code'],
						'promo_type' 	=> $data['promo_type'],
						'promo_outlet' 	=> $promo_outlet,
						'promo_id'		=> $data['id_promo_campaign'],
						'promo_date_end'=> date("d F Y H:i", strtotime($data['date_end'])),
						'promo_date_start' 	=> date("d F Y H:i", strtotime($data['date_start'])),
						'promo_created' 	=> $data['created_at'],
						'promo_used_coupon' => $data['used_code'],
						'promo_total_coupon'=> $promo_total_coupon
					];

					$data = $result;
				}

				break;

        	default:
        		$data = [];
        		break;
        }

        return response()->json($data);
    }

    function getPayment($payment)
    {
    	$custom_data 	= [];
    	$custom_request = new \Illuminate\Http\Request;
		$custom_request = $custom_request
						->setJson(new \Symfony\Component\HttpFoundation\ParameterBag($custom_data))
						->merge($custom_data);

		$payment_list 	= app($this->online_transaction)->availablePayment($custom_request);
		
		$result = null;
		foreach ($payment_list['result']??[] as $key => $value) {
			if ($value['code'] == $payment) {
				$result = $value;
				break;
			}
		}
		return $result;
    }

    function getRedirect($outlet, $product, $promo, $payment)
    {
    	$result = null;
    	if ($outlet && $product) {
    		$result = 'checkout';
    	}
    	elseif ($outlet){
    		$result = 'list product';
    	}
    	elseif ($promo) {
    		$result = 'list outlet';
    	}

    	return $result;
    }


}