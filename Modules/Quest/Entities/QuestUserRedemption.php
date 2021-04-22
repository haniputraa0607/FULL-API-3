<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestUserRedemption extends Model
{
    protected $primaryKey = 'id_quest_user_redemption';

    protected $fillable = [
        'id_quest',
        'id_user',
        'redemption_status',
        'redemption_date',
        'id_reference',
        'benefit_type'
    ];
}
