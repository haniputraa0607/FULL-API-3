<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class LogInvalidTransaction extends Model
{
    protected $table = 'log_invalid_transactions';

    protected $primaryKey = 'id_log_invalid_transaction';

    protected $fillable   = [
        'id_transaction',
        'reason',
        'tansaction_flag',
        'updated_by',
        'updated_date',
        'created_at',
        'updated_at'
    ];

}
