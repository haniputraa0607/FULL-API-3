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
        'short_description',
        'quest_rule',
        'id_product',
        'id_product_variant_group',
        'product_total',
        'trx_nominal',
        'trx_total',
        'id_product_category',
        'id_outlet',
        'id_province',
        'different_category_product',
        'different_outlet',
        'different_province',
        'id_outlet_group'
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
    public function outlet_group()
    {
        return $this->belongsTo('Modules\Outlet\Entities\OutletGroup', 'id_outlet_group');
    }
    public function province()
    {
        return $this->belongsTo('App\Http\Models\Province', 'id_province');
    }

    /**
     * Get quest progress, make sure model has attribute id_user before using this method
     * @return array
     */
    public function getProgressAttribute()
    {
        $quest_rule = $this->quest_rule;
        if (!$quest_rule) {
            if ($this->different_outlet) {
                $quest_rule = 'total_outlet';
            } elseif ($this->different_province) {
                $quest_rule = 'total_province';
            } elseif ($this->trx_total) {
                $quest_rule = 'total_transaction';
            } elseif ($this->id_product && $this->product_total) {
                $quest_rule = 'total_product';
            } else {
                $quest_rule = 'nominal_transaction';
            }
        }

        switch ($quest_rule) {
            case 'total_outlet':
                $total = $this->different_outlet;
                $progress = QuestOutletLog::where(['id_quest_detail' => $this->id_quest_detail, 'id_user' => $this->id_user])->count();
                break;

            case 'total_province':
                $total = $this->different_province;
                $progress = QuestProvinceLog::where(['id_quest_detail' => $this->id_quest_detail, 'id_user' => $this->id_user])->count();
                break;

            case 'total_transaction':
                $total = $this->trx_total;
                $progress = QuestTransactionLog::where(['id_quest_detail' => $this->id_quest_detail, 'id_user' => $this->id_user])->count();
                break;

            case 'total_product':
                $total = $this->product_total;
                $progress = QuestProductLog::where(['id_quest_detail' => $this->id_quest_detail, 'id_user' => $this->id_user])->sum('product_total');
                break;

            case 'nominal_transaction':
                $total = $this->trx_nominal;
                $progress = QuestTransactionLog::where(['id_quest_detail' => $this->id_quest_detail, 'id_user' => $this->id_user])->sum('transaction_nominal');
                break;

            default:
                return null;
        }

        if ($progress > $total) {
            $progress = $total;
        }

        return [
            'total' => $total,
            'done' => (int) $progress,
            'complete' => $this->is_done,
        ];
    }
}
