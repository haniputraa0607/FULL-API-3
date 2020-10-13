<?php

namespace Modules\ProductVariant\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionProductVariant extends Model
{
    protected $primaryKey = 'id_transaction_product_variant';

    protected $casts = [
    	'transaction_product_variant_price' => 'double'
    ];

    protected $fillable = [
    	'id_transaction_product',
    	'id_product_variant',
    	'transaction_product_variant_price'
    ];

    public function variant() {
        return $this->belongsTo(ProductVariant::class, 'id_product_variant');
    }
}
