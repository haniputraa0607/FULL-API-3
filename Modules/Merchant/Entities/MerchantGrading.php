<?php

namespace Modules\Merchant\Entities;

use Illuminate\Database\Eloquent\Model;

class MerchantGrading extends Model
{
    protected $table = 'merchant_gradings';
    protected $primaryKey = 'id_merchant_grading';

    protected $fillable = [
        'id_merchant',
        'grading_name',
        'min_qty',
        'min_nominal'
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'id_merchant');
    }
}
