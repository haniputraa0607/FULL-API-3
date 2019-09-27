<?php

namespace Modules\Brand\Entities;

use Illuminate\Database\Eloquent\Model;

class BrandOutlet extends Model
{
    protected $table = 'brand_outlet';

    protected $primaryKey = 'id_brand_outlet';

    protected $fillable   = [
        'id_brand',
        'id_outlet'
    ];

    public function outlets()
    {
        return $this->hasOne(\App\Http\Models\Outlet::class, 'id_outlet', 'id_outlet');
    }

    public function brands()
    {
        return $this->hasOne(Brand::class, 'id_brand', 'id_brand');
    }
}
