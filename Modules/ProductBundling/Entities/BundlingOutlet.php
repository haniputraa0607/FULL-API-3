<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Outlet;
use Illuminate\Database\Eloquent\Model;

class BundlingOutlet extends Model
{
    protected $table = 'bundling_outlet';

    protected $fillable = [
        'id_bundling',
        'id_outlet'
    ];

    protected $casts = [
        'id_bundling' => 'integer',
        'id_outlet' => 'integer'
    ];

    public function outlets()
    {
        return $this->hasOne(Outlet::class, 'id_outlet', 'id_outlet');
    }

    public function bundlings()
    {
        return $this->hasOne(Bundling::class, 'id_bundling', 'id_bundling');
    }
}
