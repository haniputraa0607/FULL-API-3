<?php

namespace Modules\SettingFraud\Entities;

use Illuminate\Database\Eloquent\Model;

class DailyCheckPromoCode extends Model
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
    protected $table = 'daily_check_promo_code';

    /**
     * @var array
     */
    protected $fillable = [
        'id_daily_check_promo_code',
        'id_user',
        'device_id',
        'promo_code',
        'ip',
        'created_at', 
        'updated_at'
    ];
}
