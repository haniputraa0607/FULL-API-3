<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    protected $table = 'quests';

    protected $primaryKey = 'id_quest';

    protected $fillable = [
        'name',
        'image',
        'date_start',
        'date_end',
        'publish_start',
        'publish_end',
        'description'
    ];
    
    public function getIdQuestAttribute($value) {
        return \App\Lib\MyHelper::encSlug($value);
    }
}
