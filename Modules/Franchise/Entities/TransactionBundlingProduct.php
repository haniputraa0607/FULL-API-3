<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionBundlingProduct extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'transaction_bundling_products';

    protected $primaryKey = 'id_transaction_bundling_product';

    protected $fillable   = [
        'id_transaction',
        'id_bundling',
        'id_outlet',
        'transaction_bundling_product_base_price',
        'transaction_bundling_product_subtotal',
        'transaction_bundling_product_qty',
        'transaction_bundling_product_total_discount'
    ];
}
