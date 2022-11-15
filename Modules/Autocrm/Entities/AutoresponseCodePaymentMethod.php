<?php

namespace Modules\Autocrm\Entities;

use Illuminate\Database\Eloquent\Model;

class AutoresponseCodePaymentMethod extends Model
{
    protected $table = 'autoresponse_code_payment_methods';
    protected $primaryKey = 'id_autoresponse_code_payment_method';

    protected $fillable = [
        'id_autoresponse_code',
        'autoresponse_code_payment_method'
    ];
}
