<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductCategory
 * 
 * @property int $id_product_category
 * @property int $id_parent_category
 * @property int $product_category_order
 * @property string $product_category_name
 * @property string $product_category_description
 * @property string $product_category_photo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\ProductCategory $product_category
 * @property \Illuminate\Database\Eloquent\Collection $product_categories
 * @property \Illuminate\Database\Eloquent\Collection $products
 *
 * @package App\Models
 */
class ProductCategory extends Model
{
	protected $primaryKey = 'id_product_category';

	protected $casts = [
		'id_parent_category' => 'int',
		'product_category_order' => 'int'
	];
protected $appends  = ['url_product_category_photo'];
	protected $fillable = [
		'id_parent_category',
		'product_category_order',
		'product_category_name',
		'product_category_description',
		'product_category_photo'
	];

	public function product_category()
	{
		return $this->belongsTo(\App\Http\Models\ProductCategory::class, 'id_parent_category');
	}

	public function product_categories()
	{
		return $this->hasMany(\App\Http\Models\ProductCategory::class, 'id_parent_category');
	}

	public function products()
	{
		return $this->hasMany(\App\Http\Models\Product::class, 'id_product_category');
	}
	
	public function parentCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'id_parent_category', 'id_product_category');
    }

    public function getUrlProductCategoryPhotoAttribute() {
        if (empty($this->product_category_photo)) {
            return env('APP_API_URL').'img/default.jpg';
        }
        else {
            return env('APP_API_URL').$this->product_category_photo;
        }
    }

    public function scopeId($query, $id) {
        return $query->where('id_product_category', $id);
    }

    public function scopeParents($query, $id) {
        return $query->where('id_parent_category', $id);
    }

    public function scopeMaster($query) {
        return $query->whereNull('id_parent_category');
    }
}
