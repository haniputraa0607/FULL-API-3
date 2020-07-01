<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:16 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Enquiry
 * 
 * @property int $id_enquiry
 * @property int $id_outlet
 * @property string $enquiry_name
 * @property string $enquiry_phone
 * @property string $enquiry_email
 * @property string $enquiry_subject
 * @property string $enquiry_content
 * @property string $enquiry_photo
 * @property string $enquiry_status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Outlet $outlet
 *
 * @package App\Models
 */
class Enquiry extends Model
{
	protected $primaryKey = 'id_enquiry';

	protected $casts = [
		'id_outlet' => 'int'
	];

	protected $fillable = [
		'id_brand',
		'id_outlet',
		'enquiry_name',
		'enquiry_phone',
		'enquiry_email',
		'enquiry_subject',
		'enquiry_content',
		'enquiry_photo',
		'enquiry_status',
		'enquiry_device_token',
		'reply_email_subject',
		'reply_sms_content',
		'reply_push_subject',
		'reply_push_image',
		'reply_push_clickto',
		'reply_push_link',
		'reply_push_id_reference',
	];

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet', 'id_outlet');
	}

	public function brand()
	{
		return $this->belongsTo(\Modules\Brand\Entities\Brand::class, 'id_brand', 'id_brand');
	}

	protected $appends    = ['url_enquiry_photo'];

	public function getUrlEnquiryPhotoAttribute() {
	    if (!empty($this->enquiry_photo)) {
	        return config('url.storage_url_api').$this->enquiry_photo;
	    }
	}

	public function files()
	{
		return $this->hasMany(\Modules\Enquiries\Entities\EnquiriesFile::class, 'id_enquiry');
	}
}
