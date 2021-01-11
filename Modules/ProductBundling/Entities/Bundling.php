<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class Bundling extends Model
{
    protected $table = 'bundling';
    protected $primaryKey = 'id_bundling';

    protected $fillable = [
        'bundling_code',
        'bundling_name',
        'image',
        'image_detail',
        'bundling_description',
        'all_outlet',
        'created_by',
        'start_date',
        'end_date'
    ];

    public function getImageAttribute($value) {
        return config('url.storage_url_api').$value;
    }

    public function getImageDetailAttribute($value) {
        return config('url.storage_url_api').$value;
    }

    public function user()
    {
        return $this->hasOne(User::class, 'created_by');
    }

    public function bundling_product(){
        return $this->hasMany(BundlingProduct::class, 'id_bundling', 'id_bundling')
        ->join('products', 'bundling_product.id_product', 'products.id_product');
    }
    
    public function outlets(){
		return $this->belongsToMany(Outlet::class, 'bundling_outlet', 'id_bundling', 'id_outlet');
    }
    
    public function bundling_outlet(){
        return $this->hasMany(BundlingOutlet::class, 'id_bundling', 'id_bundling')
        ->join('outlets', 'bundling_outlet.id_outlet', 'outlets.id_outlet');
    }
}
