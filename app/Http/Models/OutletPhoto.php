<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OutletPhoto
 * 
 * @property int $id_outlet_photo
 * @property int $id_outlet
 * @property string $outlet_photo
 * @property int $outlet_photo_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Outlet $outlet
 *
 * @package App\Models
 */
class OutletPhoto extends Model
{
	protected $primaryKey = 'id_outlet_photo';
	protected $appends    = ['url_outlet_photo'];
	protected $casts = [
		'id_outlet' => 'int',
		'outlet_photo_order' => 'int'
	];

	protected $fillable = [
		'id_outlet',
		'outlet_photo',
		'outlet_photo_order'
	];

	public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'id_outlet', 'id_outlet');
    }

    public function getUrlOutletPhotoAttribute() {
        if (empty($this->outlet_photo)) {
            return env('S3_URL_API').'img/default.jpg';
        }
        else {
            return env('S3_URL_API').$this->outlet_photo;
        }
    }
}
