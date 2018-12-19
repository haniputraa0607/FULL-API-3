<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\NewsProduct;

use App\Lib\MyHelper;

use Modules\Product\Http\Requests\discount\Create;
use Modules\Product\Http\Requests\discount\Update;
use Modules\Product\Http\Requests\discount\Delete;

class ApiDiskonProductController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    function checkInputDiscount($post = []) {
        $data = [];

        if (isset($post['id_product'])) {
            $data['id_product'] = $post['id_product'];
        }
        if (isset($post['discount_percentage'])) {
            $data['discount_percentage'] = $post['discount_percentage'];
        }
        if (isset($post['discount_nominal'])) {
            $data['discount_nominal'] = $post['discount_nominal'];
        }
        if (isset($post['discount_start'])) {
            $data['discount_start'] = date('Y-m-d', strtotime($post['discount_start']));
        }
        if (isset($post['discount_end'])) {
            $data['discount_end'] = date('Y-m-d', strtotime($post['discount_end']));
        }
        if (isset($post['discount_time_start'])) {
            $data['discount_time_start'] = $post['discount_time_start'];
        }
        if (isset($post['discount_time_end'])) {
            $data['discount_time_end'] = $post['discount_time_end'];
        }
        if (isset($post['discount_days'])) {
            $data['discount_days'] = $post['discount_days'];
        }

        return $data;
    }

    /**
     * create diskon product
     */
    function create(Create $request) {
        $post = $request->json()->all();

        $data = $this->checkInputDiscount($post);
        // return $data;
        $save = ProductDiscount::create($data);
  
        return response()->json(MyHelper::checkCreate($save));
    }

    /**
     * update diskon product
     */
    function update(Update $request) {
        $post = $request->json()->all();

        $data = $this->checkInputDiscount($post);

        $save = ProductDiscount::where('id_product_discount', $post['id_product_discount'])->update($data);

        return response()->json(MyHelper::checkUpdate($save));
    }

    /**
     * delete
     */
    function delete(Delete $request) {
        $delete = ProductDiscount::where('id_product_discount', $request->json('id_product_discount'))->delete();

        return response()->json(MyHelper::checkDelete($delete));
    }

}
