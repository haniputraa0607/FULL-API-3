<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\NewsProduct;
use App\Http\Models\TransactionProduct;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;

use Modules\Product\Http\Requests\Sync;

class ApiSyncProductController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    /* Sync Product*/
    function sync(Sync $request) {
        // product
        $product = $request->json('product');

        foreach ($product as $key => $value) {
            
            $data = $this->checkInputProduct($value);

            // create or update
            $save = Product::updateOrCreate(['product_code' => $data['product_code']], $data);

            if ($save) {
                continue;
            }
            else {
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to sync']
                ]);
            }
        }

        // return success
        return response()->json([
            'status' => 'success'
        ]);
    }

    /* Cek Inputan */
    function checkInputProduct($post=[]) {
        $data = [];

        // code
        if (isset($post['product_code'])) {
            $data['product_code'] = $post['product_code'];
        }
        // name
        if (isset($post['product_name'])) {
            $data['product_name'] = $post['product_name'];
        }
        // decription
        if (isset($post['product_description'])) {
            $data['product_description'] = $post['product_description'];
        }
        // price
        if (isset($post['product_price'])) {
            $data['product_price'] = $post['product_price'];
        }
        // weight
        if (isset($post['product_weight'])) {
            $data['product_weight'] = $post['product_weight'];
        }
        else {
            $data['product_weight'] = 0;
        }
        // visibility
        if (isset($post['product_visibility'])) {
            $data['product_visibility'] = $post['product_visibility'];
        }

        return $data;
    }
}
