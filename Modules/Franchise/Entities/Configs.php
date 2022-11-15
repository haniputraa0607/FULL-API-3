<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:15 +0000.
 */

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class Configs extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'configs';

    protected $primaryKey = 'id_config';

    protected $fillable   = [
        'config_name',
        'description',
        'is_active',
        'created_at',
        'updated_at'
    ];
}
