<?php

namespace Modules\Merchant\Entities;

use Illuminate\Database\Eloquent\Model;

class UserResellerMerchant extends Model
{
    protected $table = 'user_reseller_merchants';
    protected $primaryKey = 'id_user_reseller_merchant';

    protected $fillable = [
        'id_user',
        'id_merchant',
        'id_merchant_grading',
        'reseller_merchant_status',
        'id_approved',
        'notes',
        'notes_user',
    ];
}
