<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCustomerReportRegistration extends Model
{
	protected $connection = 'mysql';
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'daily_customer_report_registration';

    protected $primaryKey = 'id_daily_customer_report_registration';

    /**
     * @var array
     */
    protected $fillable = [
        'reg_date',
        'cust_total',
        'cust_male',
        'cust_female',
        'cust_android',
        'cust_ios',
        'cust_telkomsel',
        'cust_xl',
        'cust_indosat',
        'cust_tri',
        'cust_axis',
        'cust_smart',
        'cust_teens',
        'cust_young_adult',
        'cust_adult',
        'cust_old'
    ];
	
    public function membership()
    {
        return $this->belongsTo(Membership::class, 'id_membership', 'id_membership')->select('id_membership', 'membership_name');
    }
}
