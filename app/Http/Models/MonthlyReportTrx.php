<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyReportTrx extends Model
{
	protected $connection = 'mysql';
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'monthly_report_trx';

    protected $primaryKey = 'id_monthly_report_trx';

    /**
     * @var array
     */
    protected $fillable = [
        'id_outlet',
        'trx_month',
        'trx_year',
        'trx_count',
        'trx_tax',
        'trx_shipment',
        'trx_service',
        'trx_discount',
        'trx_subtotal',
        'trx_grand',
        'trx_cashback_earned',
        'trx_point_earned',
        'trx_max',
        'trx_average',
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
	
	/**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'id_outlet', 'id_outlet');
    }
}
