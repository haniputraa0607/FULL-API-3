<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class UserHairStylistTheory extends Authenticatable
{
    protected $table = 'user_hair_stylist_theories';

    protected $primaryKey = 'id_user_hair_stylist_theory';

    protected $fillable   = [
        'id_user_hair_stylist_document',
        'id_theory',
        'category_title',
        'theory_title',
        'minimum_score',
        'score',
        'passed_status'
    ];
}
