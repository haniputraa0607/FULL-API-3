<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class LogCheckPromoCode extends Model
{
    /**
	 * The database name used by the model.
	 *
	 * @var string
	 */
	protected $connection = 'mysql2';
	
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_check_promo_code';

    /**
     * @var array
     */
    protected $fillable = [
        'id_log_check_promo_code',
        'id_user',
        'device_id',
        'promo_code',
        'ip',
        'created_at', 
        'updated_at'
    ];
}
