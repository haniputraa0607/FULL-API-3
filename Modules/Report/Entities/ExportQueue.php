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
class ExportQueue extends Eloquent
{
	protected $table = 'export_queues';
	protected $primaryKey = 'id_export_queue';

	protected $fillable = [
		'id_user',
		'filter',
		'report_type',
		'url_export',
		'status_export'
	];
}
