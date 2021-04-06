<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class ManualRefund extends Model
{
    protected $fillable = [
        'id_transaction',
        'refund_date',
        'note',
        'images',
        'created_by'
    ];
}
