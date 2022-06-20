<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class UserHairStylistDocuments extends Authenticatable
{
    protected $table = 'user_hair_stylist_documents';

    protected $primaryKey = 'id_user_hair_stylist_document';

    protected $fillable   = [
        'id_user_hair_stylist',
        'id_theory_category',
        'document_type',
        'process_date',
        'process_name_by',
        'process_notes',
        'attachment',
        'conclusion_status',
        'conclusion_score'
    ];

    public function getAttachmentAttribute($value)
    {
        if(empty($value)){
            return '';
        }
        return config('url.storage_url_api') . $value;
    }

    public function theory(){
        return $this->hasMany(\Modules\Recruitment\Entities\UserHairStylistTheory::class, 'id_user_hair_stylist_document');
    }
}
