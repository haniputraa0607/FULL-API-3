<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 11 Mar 2020 14:09:26 +0700.
 */

namespace Modules\Report\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DailyReportPayment
 * 
 * @property int $id_daily_report_payment
 * @property \Carbon\Carbon $trx_date
 * @property int $payment_count
 * @property int $payment_total_nominal
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Http\Models
 */
class DailyReportPaymentDeals extends Eloquent
{
	protected $table = 'daily_report_payment_deals';
	protected $primaryKey = 'id_daily_report_payment_deals';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'date',
        'payment_type',
		'payment',
		'payment_count',
		'payment_nominal'
	];
}
