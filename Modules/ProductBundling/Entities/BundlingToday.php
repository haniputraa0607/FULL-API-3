<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class BundlingToday extends Model
{
    protected $table = 'bundling_today';
    protected $primaryKey = 'id_bundling_today';

    protected $fillable = [
        'id_bundling',
        'time_start',
        'time_end'
    ];
}
