<?php

namespace Modules\Plastic\Http\Controllers;

class Plastic
{

    public $id_product, $product_name, $product_capacity, $product_type, $total_used;

    function __construct($data){
        $this->id_product = $data['id_product'];
        $this->product_name = $data['product_name'];
        $this->product_capacity = $data['product_capacity'];
        $this->product_type = $data['product_type'];
        $this->total_used = 0;
    }
}
