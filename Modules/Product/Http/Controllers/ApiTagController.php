<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\Tag;
use App\Http\Models\ProductTag;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use DB;

use Modules\Product\Http\Requests\tag\CreateTag;
use Modules\Product\Http\Requests\tag\UpdateTag;
use Modules\Product\Http\Requests\tag\DeleteTag;
use Modules\Product\Http\Requests\tag\CreateProductTag;
use Modules\Product\Http\Requests\tag\DeleteProductTag;

class ApiTagController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    function list(Request $request){
        $post = $request->json()->all();

        $tag = Tag::with('product_tags', 'product_tags.product');

        if (isset($post['id_tag'])) {
            $tag = $tag->with('product_tags', 'product_tags.product')->where('id_tag', $post['id_tag']);
        }
        $tag = $tag->get()->toArray();
        return response()->json(MyHelper::checkGet($tag));
    }

    function create(CreateTag $request) {

        $post = $request->json()->all();
        $create = Tag::create($post);

        return response()->json(MyHelper::checkCreate($create));
    }

    function update(UpdateTag $request) {
        $tag = Tag::find($request->json('id_tag'));

        if (empty($tag)) {
            return response()->json(MyHelper::checkGet($tag));
        }

        $post = $request->json()->all();
        $update = $tag->update($post);

        return response()->json(MyHelper::checkUpdate($update));
    }

    function delete(DeleteTag $request) {
        $id = $request->json('id_tag');
        $delete = Tag::where('id_tag', $id)->delete();
        return response()->json(MyHelper::checkDelete($delete));
    }

    /* Create Relasi Product Tag */
    function createProductTag(CreateProductTag $request) {
        $data['id_product'] = $request->json('id_product');
        $data['id_tag']     = $request->json('id_tag');
        $data['created_at'] = date('Y-m-d h:i:s');
        $data['updated_at'] = date('Y-m-d h:i:s');
        
        // save
        $insert = ProductTag::insert($data);
        return response()->json(MyHelper::checkUpdate($insert));
    }

     /* Delete Product Tag */
     function deleteProductTag(DeleteProductTag $request) {
        $post = $request->json()->all();

        // delete 1 product tag
        if(isset($post['id_product_tag'])){
            $delete = ProductTag::where('id_product_tag', $request->json('id_product_tag'))->delete();
        }

        // delete all tag in product
        if(isset($post['id_product'])){
            $delete = ProductTag::where('id_product', $request->json('id_product'))->delete();
        }
        return response()->json(MyHelper::checkDelete($delete));
    }

}

