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
        'autoclaim_quest',
        'stop_at',
        'stop_reason',
        'quest_limit',
        'quest_claimed',
        'benefit_claimed',
        'max_complete_day',
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
        $result = QuestContent::where('id_quest', $this->id_quest)
            ->select('title', 'content')
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

    /**
     * Get quest progress, make sure model has attribute id_user or id_quest_user before using this method
     * @return [type] [description]
     */
    public function getProgressAttribute()
    {
        if ($this->id_quest_user) {
            $questUsers = QuestUserDetail::where(['id_quest_user' => $this->id_quest_user])->get();
        } else {
            $questUsers = QuestUserDetail::where(['id_quest' => $this->id_quest, 'id_user' => $this->id_user])->get();
        }

        if (!$questUsers->count()) {
            return null;
        }

        $result = [
            'total' => $questUsers->count(),
            'done' => $questUsers->sum('is_done'),
        ];

        $result['complete'] = $result['done'] >= $result['total'] ? 1 : 0;
        return $result;
    }

    /**
     * Get user benefit redemption status, make sure model has attribute id_user before using this method
     * @return array
     */
    public function getUserRedemptionAttribute()
    {
        return QuestUserRedemption::where(['id_quest' => $this->id_quest, 'id_user' => $this->id_user])->first();
    }
}
