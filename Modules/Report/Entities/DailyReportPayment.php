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
class DailyReportPayment extends Eloquent
{
	protected $table = 'daily_report_payment';
	protected $primaryKey = 'id_daily_report_payment';

	protected $casts = [
		'payment_count' => 'int',
		'payment_total_nominal' => 'int'
	];

	protected $dates = [
		'trx_date'
	];

	protected $fillable = [
		'trx_date',
        'refund_with_point',
        'payment_type',
		'id_outlet',
		'trx_payment',
		'trx_payment_count',
		'trx_payment_nominal'
	];
}
