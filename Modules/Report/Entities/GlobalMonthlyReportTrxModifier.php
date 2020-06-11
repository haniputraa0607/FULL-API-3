<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 09 Jun 2020 17:18:56 +0700.
 */

namespace Modules\Report\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class GlobalMonthlyReportTrxModifier
 * 
 * @property int $id_global_monthly_report_trx_modifier
 * @property int $trx_month
 * @property \Carbon\Carbon $trx_year
 * @property int $id_product_modifier
 * @property string $text
 * @property int $total_rec
 * @property int $total_qty
 * @property int $total_nominal
 * @property int $cust_male
 * @property int $cust_female
 * @property int $cust_android
 * @property int $cust_ios
 * @property int $cust_telkomsel
 * @property int $cust_xl
 * @property int $cust_indosat
 * @property int $cust_tri
 * @property int $cust_axis
 * @property int $cust_smart
 * @property int $cust_teens
 * @property int $cust_young_adult
 * @property int $cust_adult
 * @property int $cust_old
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class GlobalMonthlyReportTrxModifier extends Eloquent
{
	protected $table = 'global_monthly_report_trx_modifier';
	protected $primaryKey = 'id_global_monthly_report_trx_modifier';

	protected $fillable = [
		'trx_month',
		'trx_year',
		'id_product_modifier',
		'text',
		'total_rec',
		'total_qty',
		'total_nominal',
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
}
