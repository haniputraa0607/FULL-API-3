<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestContent extends Model
{
    protected $table = 'quest_contents';

    protected $primaryKey = 'id_quest_content';

    protected $fillable = [
        'id_quest',
        'title',
        'content',
        'order'
    ];
}
