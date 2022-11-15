<?php

namespace Modules\Franchise\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class Bundling extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'bundling';
    protected $primaryKey = 'id_bundling';

    protected $fillable = [
        'id_bundling_category',
        'bundling_code',
        'bundling_name',
        'bundling_promo_status',
        'bundling_specific_day_type',
        'bundling_price_before_discount',
        'bundling_price_after_discount',
        'image',
        'image_detail',
        'bundling_description',
        'bundling_order',
        'outlet_available_type',
        'all_outlet',
        'created_by',
        'start_date',
        'end_date'
    ];

    public function getImageAttribute($value)
    {
        return config('url.storage_url_api') . $value;
    }

    public function getImageDetailAttribute($value)
    {
        return config('url.storage_url_api') . $value;
    }

    public function user()
    {
        return $this->hasOne(User::class, 'created_by');
    }

    public function bundling_product()
    {
        return $this->hasMany(BundlingProduct::class, 'id_bundling', 'id_bundling')
        ->join('products', 'bundling_product.id_product', 'products.id_product');
    }

    public function bundling_periode_day()
    {
        return $this->hasMany(BundlingPeriodeDay::class, 'id_bundling', 'id_bundling');
    }

    public function outlets()
    {
        return $this->belongsToMany(Outlet::class, 'bundling_outlet', 'id_bundling', 'id_outlet');
    }

    public function bundling_outlet()
    {
        return $this->hasMany(BundlingOutlet::class, 'id_bundling', 'id_bundling')
        ->join('outlets', 'bundling_outlet.id_outlet', 'outlets.id_outlet');
    }

    public function bundling_outlet_group()
    {
        return $this->hasMany(BundlingOutletGroup::class, 'id_bundling', 'id_bundling');
    }

    public function category()
    {
        return $this->hasMany(BundlingCategory::class, 'id_bundling_category', 'id_bundling_category');
    }
}
