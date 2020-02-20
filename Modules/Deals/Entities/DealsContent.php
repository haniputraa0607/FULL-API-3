<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 18 Feb 2020 16:31:37 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsContent
 * 
 * @property int $id_deals_content
 * @property int $id_deals
 * @property string $title
 * @property int $order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Deals\Entities\Deal $deal
 * @property \Illuminate\Database\Eloquent\Collection $deals_content_details
 *
 * @package Modules\Deals\Entities
 */
class DealsContent extends Eloquent
{
	protected $primaryKey = 'id_deals_content';

	protected $casts = [
		'id_deals' => 'int',
		'order' => 'int',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'id_deals',
		'title',
		'order',
		'is_active'
	];

	public function deal()
	{
		return $this->belongsTo(\App\Models\Deal::class, 'id_deals');
	}

	public function deals_content_details()
	{
		return $this->hasMany(\Modules\Deals\Entities\DealsContentDetail::class, 'id_deals_content');
	}
}
