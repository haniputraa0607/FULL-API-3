<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Outlet;
use Illuminate\Database\Eloquent\Model;

class BundlingOutletGroup extends Model
{
    protected $table = 'bundling_outlet_group';

    protected $fillable = [
        'id_bundling',
        'id_outlet_group'
    ];
}
