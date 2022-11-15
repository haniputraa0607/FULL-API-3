<?php

namespace Modules\Autocrm\Entities;

use Illuminate\Database\Eloquent\Model;

class AutoresponseCodeTransactionType extends Model
{
    protected $table = 'autoresponse_code_transaction_types';
    protected $primaryKey = 'id_autoresponse_code_transaction_type';

    protected $fillable = [
        'id_autoresponse_code',
        'autoresponse_code_transaction_type'
    ];
}
