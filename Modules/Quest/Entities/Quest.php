<?php

namespace Modules\Quest\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;

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
        'short_description',
        'description',
        'is_complete',
    ];
    
    public function getImageUrlAttribute($value)
    {
        return $value ? env('STORAGE_URL_API') . $value : null;
    }

    public function quest_contents()
    {
        return $this->hasMany(QuestContent::class, 'id_quest')->orderBy('order');
    }

    public function quest_detail()
    {
        return $this->hasMany(QuestDetail::class, 'id_quest');
    }

    public function quest_benefit()
    {
        return $this->hasOne(QuestBenefit::class, 'id_quest');
    }

    public function getContentsAttribute()
    {
        $result = $this->quest_contents->toArray();
        $result = QuestContent::where('id_quest', \App\Lib\MyHelper::decSlug($this->id_quest))
            ->where('is_active', 1)
            ->orderBy('order')
            ->get()
            ->toArray();
        if ($this->description !== null) {
            array_unshift($result, [
                'title' => 'Overview',
                'content' => $this->description,
            ]);
        }
        return $result;
    }

    public function getTextLabelAttribute()
    {
        $now = date('Y-m-d H:i:s');
        $date_start = MyHelper::indonesian_date_v2($this->date_start, 'd F Y');
        $date_end = MyHelper::indonesian_date_v2($this->date_end, 'd F Y');
        if ($this->date_start > $now) {
            return [
                'text' => 'Dimulai pada '.$date_start,
                'code' => 0,
            ];
        } elseif ($this->date_start <= $now && $this->date_end >= $now) {
            return [
                'text' => 'Aktif hingga '.$date_end,
                'code' => 1,
            ];
        } else {
            return [
                'text' => 'Berakhir pada '.$date_end,
                'code' => -1,
            ];
        }
    }

    public function getProgressAttribute()
    {
        $questUsers = QuestUser::where('id_quest', \App\Lib\MyHelper::decSlug($this->id_quest))->get();

        $result = [
            'total' => $questUsers->count(),
            'done' => $questUsers->sum('is_done'),
        ];

        $result['complete'] = $result['done'] >= $result['total'] ? 1 : 0;
        return $result;
    }

}
