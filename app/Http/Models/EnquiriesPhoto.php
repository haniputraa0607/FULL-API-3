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
class EnquiriesPhoto extends Model
{
	protected $primaryKey = 'id_enquiry';

	protected $table = 'enquiries_photo';

	protected $casts = [
		'id_ep' => 'int'
	];

	protected $fillable = [
		'id_ep',
		'id_enquiry',
		'enquiry_photo',
		'created_at',
		'updated_at',
	];

	protected $appends = ['url_enquiry_photo'];

	public function getUrlEnquiryPhotoAttribute() {
	    if (!empty($this->enquiry_photo)) {
	        return env('STORAGE_URL_API').$this->enquiry_photo;
	    }
	}

}
