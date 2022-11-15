<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class FraudBetweenTransaction extends Model
{
    protected $primaryKey = 'id_fraud_between_transaction';
    protected $table = 'fraud_between_transaction';

    protected $fillable = [
        'id_fraud_detection_log_transaction_in_between',
        'id_transaction',
        'id_transaction_group'
    ];
}
