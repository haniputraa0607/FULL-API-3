<?php

/**
 * Created by Reliese Model.
 * Date: Wed, 11 Mar 2020 14:09:26 +0700.
 */

namespace Modules\Franchise\Entities;

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
class ExportFranchiseQueue extends Eloquent
{
    const REPORT_TYPE_REPORT_TRANSACTION_PRODUCT = 'Report Transaction Product';
    const REPORT_TYPE_REPORT_TRANSACTION_MODIFIER = 'Report Transaction Modifier';

    const STATUS_EXPORT_RUNNING = 'Running';
    const STATUS_EXPORT_READY = 'Ready';
    const STATUS_EXPORT_DELETED = 'Deleted';

    protected $table = 'export_franchise_queues';
    protected $primaryKey = 'id_export_franchise_queue';

    protected $fillable = [
        'id_user_franchise',
        'id_outlet',
        'filter',
        'report_type',
        'url_export',
        'status_export'
    ];
}
