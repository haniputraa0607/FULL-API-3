<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\NewsProduct;
use App\Http\Models\TransactionProduct;
use App\Http\Models\ProductPrice;
use App\Http\Models\Outlet;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;

use Modules\Product\Http\Requests\product\Create;
use Modules\Product\Http\Requests\product\Update;
use Modules\Product\Http\Requests\product\Delete;
use Modules\Product\Http\Requests\product\UploadPhoto;
use Modules\Product\Http\Requests\product\UpdatePhoto;
use Modules\Product\Http\Requests\product\DeletePhoto;
use Modules\Product\Http\Requests\product\Import;
use Modules\Product\Http\Requests\product\UpdateAllowSync;

class ApiProductController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/product/item/";

    function checkInputProduct($post=[], $type=null) {
    	$data = [];

    	if (empty($post['id_product_category']) || isset($post['id_product_category'])) {

            if (empty($post['id_product_category'])) {
                $data['id_product_category'] = NULL;
            }
            else {
                $data['id_product_category'] = $post['id_product_category'];
            }
    	}
    	if (isset($post['product_code'])) {
    		$data['product_code'] = $post['product_code'];
    	} else {
            $data['product_code'] = MyHelper::createrandom(3);
        }
    	if (isset($post['product_name'])) {
    		$data['product_name'] = $post['product_name'];
    	}
        if (isset($post['product_name_pos'])) {
            $data['product_name_pos'] = $post['product_name_pos'];
        }
    	if (isset($post['product_description'])) {
    		$data['product_description'] = $post['product_description'];
    	}
    	if (isset($post['product_video'])) {
    		$data['product_video'] = $post['product_video'];
    	}
    	if (isset($post['product_price'])) {
    		$data['product_price'] = $post['product_price'];
    	}
    	if (isset($post['product_weight'])) {
    		$data['product_weight'] = $post['product_weight'];
    	}
    	if (isset($post['product_visibility'])) {
    		$data['product_visibility'] = $post['product_visibility'];
    	}
    	if (isset($post['product_order'])) {
    		$data['product_order'] = $post['product_order'];
    	}

        // search position
        if ($type == "create") {
            if (isset($post['id_product_category'])) {
                $data['position'] = $this->searchLastSorting($post['id_product_category']);
            }
            else {
                $data['position'] = $this->searchLastSorting(null);
            }    
        }

    	return $data;
    }

    /**
     * cari urutan ke berapa
     */
    function searchLastSorting($id_product_category=null) {
        $sorting = Product::select('position')->orderBy('position', 'DESC');
        
        if (is_null($id_product_category)) {
            $sorting->whereNull('id_product_category');
        }
        else {
            $sorting->where('id_product_category', $id_product_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        }
        else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->position)) {
                return 1;
            }
            else {
                $sorting = $sorting->position + 1;
                return $sorting;
            }
        }
    }

    public function priceUpdate(Request $request) {
		$post = $request->json()->all();
		foreach ($post['id_product_price'] as $key => $id_product_price) {
			if($id_product_price == 0){
				$update = ProductPrice::create(['id_product' => $post['id_product'],
												'id_outlet' => $post['id_outlet'][$key],
												'product_price' => $post['product_price'][$key],
												'product_price_base' => $post['product_price_base'][$key],
												'product_price_tax' => $post['product_price_tax'][$key],
												'product_stock_status' => $post['product_stock_status'][$key],
												'product_visibility' => "'".$post['product_visibility'][$key]."'"]);
			}
			else{
				$update = ProductPrice::where('id_product_price','=',$id_product_price)->update(['product_price' => $post['product_price'][$key], 'product_price_base' => $post['product_price_base'][$key], 'product_price_tax' => $post['product_price_tax'][$key],'product_stock_status' => $post['product_stock_status'][$key],'product_visibility' => $post['product_visibility'][$key]]);
			}
		}
		return response()->json(MyHelper::checkUpdate($update));
	}
	
    public function categoryAssign(Request $request) {
		$post = $request->json()->all();
		foreach ($post['id_product'] as $key => $idprod) {
			if($post['id_product_category'][$key] == 0)
				$update = Product::where('id_product','=',$idprod)->update(['id_product_category' => null, 'product_name' => $post['product_name'][$key]]);
			else
				$update = Product::where('id_product','=',$idprod)->update(['id_product_category' => $post['id_product_category'][$key], 'product_name' => $post['product_name'][$key]]);
		}
		return response()->json(MyHelper::checkUpdate($update));
	}
	
    public function import(Import $request) {
        $post = $request->json()->all();
        // return $post;
        $dataProduct = [];
        foreach ($post['data'] as $key => $value) {
            if ($key < 1) {
                foreach ($value as $row => $product) {
                    // return $product;
                    $data = [
                        'product_code'        => $value[$row]['product_code'],
                        'product_name'        => $value[$row]['product_name'],
                        'product_name_pos'    => $value[$row]['product_name_pos'],
                        'product_description' => $value[$row]['product_description'],
                        'product_video'       => $value[$row]['product_video'],
                        'product_weight'      => $value[$row]['product_weight'],
                    ];

                    $insert = Product::updateOrCreate(['product_code' => $product['product_code']], $data);
                }
                
            } else {
                foreach ($value as $row => $price) {
                    $id_product = Product::where('product_code', $price['product_code'])->first();
                    $id_outlet = Outlet::where('outlet_code', $price['outlet_code'])->first();
                    $data = [
                        'id_product'         => $id_product['id_product'],
                        'id_outlet'          => $id_outlet['id_outlet'],
                        'product_price'      => $price['product_price'],
                        'product_visibility' => $price['product_visibility'],
                    ];

                    $insert = ProductPrice::updateOrCreate(['id_product' => $id_product['id_product'], 'id_outlet' => $id_outlet['id_outlet']], $data);
                }
            }
        }

        return response()->json(MyHelper::checkCreate($insert));
    }

    /* Pengecekan code unique */
    function cekUnique($id, $code) {
        $cek = Product::where('product_code', $code)->first();

        if (empty($cek)) {
            return true;
        }
        else {
            if ($cek->id_product == $id) {
                return true;
            }
            else {
                return false;
            }
        }
    }

    /**
     * list product
     */
    function listProduct(Request $request) {
        $post = $request->json()->all();
		
		if (isset($post['id_outlet'])) {
            $product = Product::join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$post['id_outlet'])
									->where('product_prices.product_visibility','=','Visible')
                                    ->where('product_prices.product_status','=','Active')
                                    ->with(['category', 'discount']);
                                    
            if (isset($post['visibility'])) {

                if($post['visibility'] == 'Hidden'){
                    $idVisible = ProductPrice::where(function($query){
                        $query->where('product_visibility', 'Visible')
                                ->orWhere(function($q){
                                    $q->where('product_visibility', 'Hidden')
                                    ->where('product_status', 'Inactive');
                                });
                    })
                    ->where('id_outlet', $post['id_outlet'])
                    ->select('id_product')->get();
                    $product = Product::whereNotIn('products.id_product', $idVisible)->with(['category', 'discount']);
                }

                unset($post['id_outlet']);
            }
		} else {
			$product = Product::with(['category', 'discount']);
		}
		
        if (isset($post['id_product'])) {
            $product->where('products.id_product', $post['id_product']);
        }

        if (isset($post['product_code'])) {
            $product->with('product_tags')->where('products.product_code', $post['product_code']);
        }

        if (isset($post['product_name'])) {
            $product->where('products.product_name', 'LIKE', '%'.$post['product_name'].'%');
        }

        $product = $product->orderBy('position');

        if(isset($post['pagination'])){
            $product = $product->paginate(10);
        }else{
            $product = $product->get();
        }


        if (!empty($product)) {
            foreach ($product as $key => $value) {
                unset($product[$key]['product_price_base']);
                unset($product[$key]['product_price_tax']);
                $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("'.env('APP_API_URL').'", product_photo)), "'.env('APP_API_URL').'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
            }
        }

        $product = $product->toArray();

        return response()->json(MyHelper::checkGet($product));
    }

    /**
     * create  product
     */
    function create(Create $request) {
        $post = $request->json()->all();

        // check data
        $data = $this->checkInputProduct($post, $type="create");
        
        $save = Product::create($data);
		
		if($save){
			$listOutlet = Outlet::get()->toArray();
			foreach($listOutlet as $outlet){
				$data = [];
				$data['id_product'] = $save->id_product;
				$data['id_outlet'] = $outlet['id_outlet'];
				$data['product_price'] = 0;
				$data['product_visibility'] = 'Visible';
				
				ProductPrice::create($data);
			}
		}
		
        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update product
     */
    function update(Update $request) {
    	$post = $request->json()->all();

    	// check data
    	$data = $this->checkInputProduct($post);

        if (isset($post['product_code'])) {
            if (!$this->cekUnique($post['id_product'], $post['product_code'])) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['product code already used.']
                ]);
            }
        }

    	$save = Product::where('id_product', $post['id_product'])->update($data);
    	
    	if($save){
            if(isset($post['photo'])){
                //delete all photo
                $delete = $this->deletePhoto($post['id_product']);
                
                if($delete){

                    //create photo
                    $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);
                   
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $dataPhoto['product_photo'] = $upload['path'];
                    }
                    else {
                        $result = [
                            'status'   => 'fail',
                            'messages' => ['fail upload image']
                        ];
    
                        return response()->json($result);
                    }
    
                    $dataPhoto['id_product']          = $post['id_product'];
                    $dataPhoto['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
                    $save                        = ProductPhoto::create($dataPhoto);
                }
                

            }
        }


    	return response()->json(MyHelper::checkUpdate($save));
    }

    /**
     * delete product
     */
    function delete(Delete $request) {
        $product = Product::with('prices')->find($request->json('id_product'));

    	$check = $this->checkDeleteProduct($request->json('id_product'));

    	if ($check) {
    		// delete photo
    		$deletePhoto = $this->deletePhoto($request->json('id_product'));
    		
    		// delete product
    		$delete = Product::where('id_product', $request->json('id_product'))->delete();

            if($delete){
                $result = [
                    'status' => 'success',
                    'product' => [
                        'id_product' => $product['id_product'],
                        'plu_id' => $product['product_code'],
                        'product_name' => $product['product_name'],
                        'product_name_pos' => $product['product_name_pos'],
                        'product_prices' => $product['prices'],
                    ],
                ];
            } 
			else{
                $result = ['status' => 'fail', 'messages' => ['failed to delete data']];
            }

    		return response()->json($result);

    	}
    	else {
    		return response()->json([
				'status'   => 'fail',
				'messages' => ['product has been used.']
    		]);
    	}
    	
    }

    /**
     * delete photo product
     */
    function deletePhoto($id) {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product')->get()->toArray();

        if (!empty($dataPhoto)) {
            foreach ($dataPhoto as $key => $value) {
                MyHelper::deletePhoto($value['product_photo']);
            }
        }

    	$delete = ProductPhoto::where('id_product', $id)->delete();

    	return $delete;
    }

    /**
     * checking delete 
     */
    function checkDeleteProduct($id) {

    	// jika true semua maka boleh dihapus
    	if ( ($this->checkAtNews($id)) && ($this->checkAtTrx($id)) && $this->checkAtDiskon($id)) {
    		return true;
    	}
    	// klo ada yang sudah digunakan
    	else {
    		return false;
    	}
    }

    // check produk di transaksi
    function checkAtTrx($id) {
    	$check = TransactionProduct::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    // check product di diskon
    function checkAtDiskon($id) {
    	$check = ProductDiscount::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    // check product di news
    function checkAtNews($id) {
    	$check = NewsProduct::where('id_product', $id)->count();

    	if ($check > 0) {
    		return false;
    	}
    	else {
    		return true;
    	}
    }

    /**
     * upload photo
     */
    function uploadPhotoProduct(UploadPhoto $request) {
    	$post = $request->json()->all();

    	$data = [];

    	if (isset($post['photo'])) {
    	    
    	    $upload = MyHelper::uploadPhotoStrict($post['photo'], $this->saveImage, 300, 300);

    	    if (isset($upload['status']) && $upload['status'] == "success") {
    	        $data['product_photo'] = $upload['path'];
    	    }
    	    else {
    	        $result = [
    	            'status'   => 'fail',
    	            'messages' => ['fail upload image']
    	        ];

    	        return response()->json($result);
    	    }
    	}

    	if (empty($data)) {
    		return reponse()->json([
    			'status' => 'fail',
    			'messages' => ['fail save to database']
    		]);
    	}
    	else {
            $data['id_product']          = $post['id_product'];
            $data['product_photo_order'] = $this->cekUrutanPhoto($post['id_product']);
            $save                        = ProductPhoto::create($data);

    		return response()->json(MyHelper::checkCreate($save));
    	}
    }

    /*
    cek urutan
    */
    function cekUrutanPhoto($id) {
        $cek = ProductPhoto::where('id_product', $id)->orderBy('product_photo_order', 'DESC')->first();

        if (empty($cek)) {
            $cek = 1;   
        }
        else {
            $cek = $cek->product_photo_order + 1;
        }

        return $cek;
    }

    /**
     * update photo
     */
    function updatePhotoProduct(UpdatePhoto $request) {
        $update = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->update([
            'product_photo_order' => $request->json('product_photo_order')
        ]);

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * delete photo
     */
    function deletePhotoProduct(DeletePhoto $request) {
        // info photo
        $dataPhoto = ProductPhoto::where('id_product_photo')->get()->toArray();
        
        $delete    = ProductPhoto::where('id_product_photo', $request->json('id_product_photo'))->delete();

        if (!empty($dataPhoto)) {
            MyHelper::deletePhoto($dataPhoto[0]['product_photo']);
        }

        return response()->json(MyHelper::checkDelete($delete));
    }

    /* harga */
    function productPrices(Request $request) 
    {
        $data = [];
        $post = $request->json()->all();

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }

        if (isset($post['product_price'])) {
            $data['product_price'] = $post['product_price'];
        }

        if (isset($post['product_price_base'])) {
            $data['product_price_base'] = $post['product_price_base'];
        }

        if (isset($post['product_price_tax'])) {
            $data['product_price_tax'] = $post['product_price_tax'];
        }

        if (isset($post['product_visibility'])) {
            $data['product_visibility'] = $post['product_visibility'];
        }

        if (isset($post['id_outlet'])) {
            $data['id_outlet'] = $post['id_outlet'];
        }

        if (isset($post['product_stock_status'])) {
            $data['product_stock_status'] = $post['product_stock_status'];
        }

        $save = ProductPrice::updateOrCreate([
            'id_product' => $data['id_product'], 
            'id_outlet'  => $data['id_outlet']
        ], $data);

        return response()->json(MyHelper::checkUpdate($save));
    }

    function updateAllowSync(UpdateAllowSync $request) {
        $post = $request->json()->all();
        
        if($post['product_allow_sync'] == "true"){
            $allow = '1';
        }else{
            $allow = '0';
        }
    	$update = Product::where('id_product', $post['id_product'])->update(['product_allow_sync' => $allow]);

    	return response()->json(MyHelper::checkUpdate($update));
    }

    function visibility(Request $request) 
    {
        $post = $request->json()->all();
        foreach ($post['id_visibility'] as $key => $value) {
            if($value){
                $id = explode('/', $value);
                $save = ProductPrice::updateOrCreate(['id_product' => $id[0], 'id_outlet' => $id[1]], ['product_visibility' => $post['visibility']]);
                if(!$save){
                    return response()->json(MyHelper::checkUpdate($save));
                }
            }
        }

        return response()->json(MyHelper::checkUpdate($save));
    }


    /* product position */
    public function positionProductAssign(Request $request)
    {
        $post = $request->json()->all();

        if (!isset($post['product_ids'])) {
            return [
                'status' => 'fail',
                'messages' => ['Product id is required']
            ];
        }
        // update position
        foreach ($post['product_ids'] as $key => $product_id) {
            $update = Product::find($product_id)->update(['position'=>$key+1]);
        }

        return ['status' => 'success'];
    }
}
