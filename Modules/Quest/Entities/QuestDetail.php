<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestDetail extends Model
{
    protected $table = 'quest_details';

    protected $primaryKey = 'id_quest_detail';

    protected $fillable = [
        'id_quest',
        'name',
        'id_product',
        'product_total',
        'trx_nominal',
        'trx_total',
        'id_product_category',
        'id_outlet',
        'id_province',
        'different_category_product',
        'different_outlet',
        'different_province'
    ];

    public function product()
    {
        return $this->belongsTo('App\Http\Models\Product', 'id_product');
    }
    public function product_category()
    {
        return $this->belongsTo('App\Http\Models\ProductCategory', 'id_product_category');
    }
    public function outlet()
    {
        return $this->belongsTo('App\Http\Models\Outlet', 'id_outlet');
    }
    public function province()
    {
        return $this->belongsTo('App\Http\Models\Province', 'id_province');
    }
}
