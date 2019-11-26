<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 *
 * @property int $id_product
 * @property int $id_product_category
 * @property string $product_code
 * @property string $product_name
 * @property string $product_name_pos
 * @property string $product_description
 * @property string $product_video
 * @property int $product_weight
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Http\Models\ProductCategory $product_category
 * @property \Illuminate\Database\Eloquent\Collection $deals
 * @property \Illuminate\Database\Eloquent\Collection $news
 * @property \Illuminate\Database\Eloquent\Collection $product_discounts
 * @property \Illuminate\Database\Eloquent\Collection $product_photos
 * @property \Illuminate\Database\Eloquent\Collection $product_prices
 * @property \Illuminate\Database\Eloquent\Collection $transactions
 *
 * @package App\Models
 */
class Product extends Model
{
	protected $primaryKey = 'id_product';

	protected $casts = [
		'id_product_category' => 'int',
		'product_weight' => 'int'
	];

	protected $fillable = [
		'id_product_category',
		'product_code',
		'product_name',
		'product_name_pos',
		'product_description',
		'product_video',
		'product_weight',
		'product_allow_sync',
		'product_visibility',
		'position'
	];
	public function getPhotoAttribute() {
		return env('S3_URL_API').($this->photos[0]['product_photo']??'img/product/item/default.png');
	}
	public function product_category()
	{
		return $this->belongsTo(\App\Http\Models\ProductCategory::class, 'id_product_category');
	}

	 public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'id_product_category', 'id_product_category');
    }

    public function photos() {
        return $this->hasMany(ProductPhoto::class, 'id_product', 'id_product')->orderBy('product_photo_order', 'ASC');
    }

    public function discount() {
        return $this->hasMany(ProductDiscount::class, 'id_product', 'id_product');
    }

	public function deals()
	{
		return $this->hasMany(\App\Http\Models\Deal::class, 'id_product');
	}

	public function news()
	{
		return $this->belongsToMany(\App\Http\Models\News::class, 'news_products', 'id_product', 'id_news');
	}

	public function product_discounts()
	{
		return $this->hasMany(\App\Http\Models\ProductDiscount::class, 'id_product');
	}

	public function product_photos()
	{
		return $this->hasMany(\App\Http\Models\ProductPhoto::class, 'id_product');
	}

	public function product_prices()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product')->where('product_visibility', 'Visible');
	}

	public function prices()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product')->join('outlets', 'product_prices.id_outlet', 'outlets.id_outlet')->select('id_product', 'outlets.id_outlet', 'product_price');
	}

	public function transactions()
	{
		return $this->belongsToMany(\App\Http\Models\Transaction::class, 'transaction_products', 'id_product', 'id_transaction')
					->withPivot('id_transaction_product', 'transaction_product_qty', 'transaction_product_price', 'transaction_product_subtotal', 'transaction_product_note')
					->withTimestamps();
	}

	public function product_tags()
	{
		return $this->hasMany(\App\Http\Models\ProductTag::class, 'id_product');
	}

	public function product_price_hiddens()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product')->where('product_visibility', 'Hidden');
	}
	public function all_prices()
	{
		return $this->hasMany(\App\Http\Models\ProductPrice::class, 'id_product');
	}
	public function brands()
    {
        return $this->belongsToMany(\Modules\Brand\Entities\Brand::class, 'brand_product','id_product','id_brand');
    }
	public function brand_category()
    {
        return $this->hasMany(\Modules\Brand\Entities\BrandProduct::class, 'id_product','id_product')->select('id_brand','id_product_category','id_product');
    }
}
