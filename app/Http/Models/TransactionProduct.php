<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransactionProduct
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
class TransactionProduct extends Model
{
	protected $primaryKey = 'id_transaction_product';

	protected $casts = [
		'id_transaction' => 'int',
		'id_product' => 'int',
		'transaction_product_qty' => 'int',
// 		'transaction_product_price' => 'int',
		'transaction_product_subtotal' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'id_product',
		'id_outlet',
		'id_brand',
		'id_user',
		'transaction_product_qty',
		'transaction_product_price',
		'transaction_product_price_base',
		'transaction_product_price_tax',
		'transaction_product_subtotal',
		'transaction_product_note'
	];
	
	public function modifiers()
	{
		return $this->hasMany(\App\Http\Models\TransactionProductModifier::class, 'id_transaction_product');
	}
	
	public function product()
	{
		return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}
	
	 public function getUserAttribute() {
        $user = $this->transaction->user;
        return $user;
    }

    public function getProductCategoryAttribute() {
        $category = $this->product->category;
        return $category;
    }

    public function getPhotoAttibute() {
        $photo = $this->product->photos;
        return $photo;
    }

    public function getCityAttribute() {
        $city = $this->transaction->user->city;
        return $city;
    }

    public function getProvinceAttibute() {
        $province = $this->transaction->user->city->province;
        return $province;
    }
}
