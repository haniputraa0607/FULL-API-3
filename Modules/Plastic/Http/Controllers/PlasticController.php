<?php

namespace Modules\Plastic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

use Modules\Plastic\Http\Controllers\Plastic;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Plastic\Http\Requests\PlasticTotalPrice;


class PlasticController extends Controller
{

    public function data2Object($data){
        $class_object = new Plastic($data);
        return $class_object;
    }

    public function comparatorMax($object1, $object2){
        return $object1->product_capacity < $object2->product_capacity;
    }

    public function comparatorMin($object1, $object2){
        return $object1->product_capacity > $object2->product_capacity;
    }

    public function check($post)
    {
        /* Request
            item : array:[id_product, qty]
            id outlet : integer:[0..9]
        */

        if(!isset($post['id_outlet'])){
            return ['status' => 'fail', 'id_outlet is empty'];
        }

        if(!isset($post['item'])){
            return ['status' => 'fail', 'item is empty'];
        }
        
        $id_outlet = $post['id_outlet'];
        $item = $post['item'];

        // count total penggunaan plastik 
        $total_capacities = 0;
        foreach($item as $key => $value){
            $product = Product::select('id_product', 'plastic_used')->where('id_product',$value['id_product'])->first();
            if($product['plastic_used'] != null){
                $total_capacities += ($value['qty'] * $product['plastic_used']);
            }
        }

        // get all product with type plastic
        $plastics = Product::where('product_type', 'plastic')->where('product_visibility', 'Visible')->get()->toArray();
        
        if(!empty($plastics)){
            // sort array based on capacity (descending)
            $sorted_plastic_max = array_map(array($this,'data2Object'), $plastics);
            $sorted_plastic_min = $sorted_plastic_max;
    
            usort($sorted_plastic_max, array($this,'comparatorMax'));
            usort($sorted_plastic_min, array($this,'comparatorMin'));
            
            $sorted_plastic_max = json_decode(json_encode($sorted_plastic_max), true);
            $sorted_plastic_min = json_decode(json_encode($sorted_plastic_min), true);

            if($total_capacities > 0){
    
                // change key, associative with product name
                $new_array = [];
                foreach($sorted_plastic_max as $key => $value){
                    $new_array[$value['product_name']] = $value;
                }
                $sorted_plastic_max = $new_array;
                
                $new_array = [];
                foreach($sorted_plastic_min as $key => $value){
                    $new_array[$value['product_name']] = $value;
                }
                $sorted_plastic_min = $new_array;
    
                // determmine type and amount of plastic used based on product's plastic used 
                $capacity_left = $total_capacities;
    
                foreach($sorted_plastic_max as $key_max => $item_max){
                    // simpan kapasitas sebelum dan kapasitas sekarang
                    $capacity_left_before = $capacity_left;
                    $capacity_left =  $capacity_left % $item_max['product_capacity']; //6
                    
                    if($capacity_left_before > $item_max['product_capacity']){
                        // hitung kapasitas yang digunakan
                        $used_capacity = $capacity_left_before - $capacity_left;
    
                        // hitung total penggunaan plastik
                        $sorted_plastic_max[$key_max]['total_used'] = $used_capacity / $item_max['product_capacity'];
    
                        if($capacity_left != 0){
                            foreach($sorted_plastic_min as $key_min => $item_min){
                                if($capacity_left <= $item_min['product_capacity']){
                                    $capacity_left = 0;
                                    $sorted_plastic_max[$key_min]['total_used']++;
                                    break;
                                }
                            }
                        }
                    }else{
                        foreach($sorted_plastic_min as $key_min => $item_min){
                            if($capacity_left_before <= $item_min['product_capacity']){
                                $capacity_left = 0;
                                $sorted_plastic_max[$key_min]['total_used']++;
                                break;
                            }
                        }
                        // $sorted_plastic_max[$key_max]['total_used']++;
                    }
    
                    if($capacity_left == 0){
                        break;
                    }
                }
            }

            // calculate total price
            $total_plastic_price = 0;

            // cek if outlet used different prices
            $outlet = Outlet::where('id_outlet', $id_outlet)->where('outlet_different_price', 1)->first();
            foreach($sorted_plastic_max as $key => $value){
                if($outlet){
                    // get product price from product special prices
                    $product_price =  ProductSpecialPrice::where('id_outlet', $id_outlet)->where('id_product', $value['id_product'])->first()['product_special_price'];
                }else{
                    $product_price = ProductGlobalPrice::where('id_product', $value['id_product'])->first()['product_global_price'];
                }   
                $sorted_plastic_max[$key]['plastic_price_raw'] = $value['total_used'] * $product_price; 
                $total_plastic_price += $sorted_plastic_max[$key]['plastic_price_raw'];
            }

            return ['status' => 'success', 'result' => ['item' => array_values($sorted_plastic_max), 'plastic_price_total' => $total_plastic_price]];
        }
        
        return ['status' => 'fail', 'message' => 'Item Plastic is Empty'];

    }

}
