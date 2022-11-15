<?php

namespace Modules\Autocrm\Entities;

use Illuminate\Database\Eloquent\Model;

class AutoresponseCode extends Model
{
    protected $table = 'autoresponse_codes';
    protected $primaryKey = 'id_autoresponse_code';

    protected $fillable = [
        'autoresponse_code_name',
        'autoresponse_code_periode_start',
        'autoresponse_code_periode_end',
        'is_all_transaction_type',
        'is_all_payment_method',
        'is_stop'
    ];

    public function transaction_type()
    {
        return $this->hasMany(\Modules\Autocrm\Entities\AutoresponseCodeTransactionType::class, 'id_autoresponse_code');
    }

    public function payment_method()
    {
        return $this->hasMany(\Modules\Autocrm\Entities\AutoresponseCodePaymentMethod::class, 'id_autoresponse_code');
    }
}
