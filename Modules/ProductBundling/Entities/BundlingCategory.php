<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class BundlingCategory extends Model
{
    protected $table = 'bundling_categories';
    protected $primaryKey = 'id_bundling_category';

    protected $fillable = [
        'id_parent_category',
        'bundling_category_name',
        'bundling_category_description',
        'bundling_category_order'
    ];

    public function parentCategory()
    {
        return $this->belongsTo(BundlingCategory::class, 'id_parent_category', 'id_bundling_category');
    }

    public function scopeId($query, $id)
    {
        return $query->where('id_bundling_category', $id);
    }

    public function scopeParents($query, $id)
    {
        return $query->where('id_parent_category', $id);
    }

    public function scopeMaster($query)
    {
        return $query->where('id_parent_category', 0);
    }
}
