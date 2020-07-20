<?php

namespace Modules\Enquiries\Entities;

use Illuminate\Database\Eloquent\Model;

class EnquiriesFile extends Model
{
    protected $primaryKey = 'id_enquiry';

	protected $table = 'enquiries_files';

	protected $casts = [
		'id_enquiry_file' => 'int'
	];

	protected $fillable = [
		'id_enquiry_file',
		'id_enquiry',
		'enquiry_file',
		'created_at',
		'updated_at',
	];

	protected $appends = ['url_enquiry_file'];

	public function getUrlEnquiryFileAttribute() {
	    if (!empty($this->enquiry_file)) {
	        return config('url.storage_url_api').$this->enquiry_file;
	    }
	}
}
