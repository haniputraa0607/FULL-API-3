<?php

namespace Modules\Autocrm\Entities;

use Illuminate\Database\Eloquent\Model;

class AutoresponseCodeList extends Model
{
    protected $table = 'autoresponse_code_list';
    protected $primaryKey = 'id_autoresponse_code_list';

    protected $fillable = [
        'autoresponse_code',
        'id_user',
        'id_transaction'
    ];
}
