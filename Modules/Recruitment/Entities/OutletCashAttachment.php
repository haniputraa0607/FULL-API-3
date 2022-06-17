<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletCashAttachment extends Model
{
    protected $table = 'outlet_cash_attachment';
	protected $primaryKey = 'id_outlet_cash_attachment';

	protected $fillable = [
        'id_outlet_cash',
	    'outlet_cash_attachment',
        'outlet_cash_attachment_name'
	];

    public function getOutletCashAttachmentAttribute($value)
    {
        if(!empty($value)){
            return config('url.storage_url_api').$value;
        }
    }
}
