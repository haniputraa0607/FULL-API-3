<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestUserDetail extends Model
{
    protected $table = 'quest_user_details';

    protected $primaryKey = 'id_quest_user_detail';

    protected $fillable = [
        'id_quest',
        'id_quest_user',
        'id_quest_detail',
        'id_user',
        'is_done',
        'json_rule',
        'json_rule_enc',
        'date'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
    }
}
