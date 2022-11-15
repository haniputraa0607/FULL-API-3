<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionConsultationRecomendation
 *
 * @property int $id_transaction_product
 * @property int $id_transaction
 * @property int $id_product
 * @property int $transaction_product_qty
 * @property int $transaction_product_price
 * @property int $transaction_product_subtotal
 * @property string $transaction_product_note
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\Product $product
 * @property \App\Http\Models\Transaction $transaction
 *
 * @package App\Models
 */
class TransactionConsultationRecomendation extends model
{
    protected $primaryKey = 'id_transaction_consultation_recomendation';

    protected $casts = [
        'id_outlet' => 'int',
        'id_product' => 'int'
    ];

    protected $fillable = [
        'id_transaction_consultation',
        'id_product',
        'id_product_variant_group',
        'product_type',
        'qty_product',
        'qty_product_counter',
        'qty_product_redeem',
        'id_outlet',
        'treatment_description',
        'usage_rules',
        'usage_rules_time',
        'usage_rules_additional_time'
    ];

    public function consultation()
    {
        return $this->belongsTo(\App\Http\Models\TransactionConsultation::class, 'id_transaction_consultation');
    }

    public function scopeOnlyProduct($query)
    {
        return $query->where('product_type', "product");
    }

    public function scopeOnlyDrug($query)
    {
        return $query->where('product_type', "drug");
    }

    public function product()
    {
        return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
    }

    public function outlet($query)
    {
        return $query->where('outlet', "id_outlet");
    }

    public function getOutlet()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
    }
}
