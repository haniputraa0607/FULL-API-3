<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 09 Jun 2020 17:18:43 +0700.
 */

namespace Modules\Report\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class GlobalDailyReportTrxModifier
 * 
 * @property int $id_global_daily_report_trx_modifier
 * @property \Carbon\Carbon $trx_date
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
class GlobalDailyReportTrxModifier extends Eloquent
{
	protected $table = 'global_daily_report_trx_modifier';
	protected $primaryKey = 'id_global_daily_report_trx_modifier';

	protected $casts = [
		'id_product_modifier' => 'int',
		'total_rec' => 'int',
		'total_qty' => 'int',
		'total_nominal' => 'int',
		'cust_male' => 'int',
		'cust_female' => 'int',
		'cust_android' => 'int',
		'cust_ios' => 'int',
		'cust_telkomsel' => 'int',
		'cust_xl' => 'int',
		'cust_indosat' => 'int',
		'cust_tri' => 'int',
		'cust_axis' => 'int',
		'cust_smart' => 'int',
		'cust_teens' => 'int',
		'cust_young_adult' => 'int',
		'cust_adult' => 'int',
		'cust_old' => 'int'
	];

	protected $dates = [
		'trx_date'
	];

	protected $fillable = [
		'trx_date',
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
