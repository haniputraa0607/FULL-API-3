<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CrmUserData extends Model
{
    protected $table = 'crm_user_data';
    protected $primaryKey = 'crm_user_data_id';

    protected $fillable = [
        'id_user',
        'name',
        'phone',
        'email',
        'recency',
        'frequency',
        'monetary_value',
        'r_quartile',
        'f_quartile',
        'm_quartile',
        'RFMScore'
    ];
}
