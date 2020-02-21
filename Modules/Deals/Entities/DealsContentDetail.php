<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 18 Feb 2020 16:31:44 +0700.
 */

namespace Modules\Deals\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class DealsContentDetail
 * 
 * @property int $id_deals_content_detail
 * @property int $id_deals_content
 * @property string $content
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \Modules\Deals\Entities\DealsContent $deals_content
 *
 * @package Modules\Deals\Entities
 */
class DealsContentDetail extends Eloquent
{
	protected $primaryKey = 'id_deals_content_detail';

	protected $casts = [
		'id_deals_content' => 'int',
		'order' => 'int'
	];

	protected $fillable = [
		'id_deals_content',
		'content',
		'order'
	];

	public function deals_content()
	{
		return $this->belongsTo(\Modules\Deals\Entities\DealsContent::class, 'id_deals_content');
	}
}
