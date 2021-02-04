<?php

namespace Modules\ProductBundling\Entities;

use App\Http\Models\Product;
use Illuminate\Database\Eloquent\Model;

class BundlingPeriodeDay extends Model
{
    protected $table = 'bundling_periode_day';
    protected $primaryKey = 'id_bundling_periode_day';

    protected $fillable = [
        'id_bundling',
        'day',
        'time_start',
        'time_end'
    ];
}
