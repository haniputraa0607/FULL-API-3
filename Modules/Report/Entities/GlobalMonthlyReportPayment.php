<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 11 Mar 2020 14:09:54 +0700.
 */

namespace Modules\Report\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class GlobalMonthlyReportPayment
 * 
 * @property int $id_global_monthly_report_payment
 * @property int $payment_month
 * @property \Carbon\Carbon $payment_year
 * @property int $payment_count
 * @property int $payment_total_nominal
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Report\Entities
 */
class GlobalMonthlyReportPayment extends Eloquent
{
	protected $table = 'global_monthly_report_payment';
	protected $primaryKey = 'id_global_monthly_report_payment';

	protected $casts = [
		'payment_month' => 'int',
		'payment_count' => 'int',
		'payment_total_nominal' => 'int'
	];

	protected $dates = [
		'payment_year'
	];

	protected $fillable = [
		'trx_month',
		'trx_year',
		'trx_payment',
		'trx_payment_count',
		'trx_payment_nominal'
	];
}
