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
        'user_rule_subject',
        'user_rule_parameter',
        'user_rule_operator'
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

    public function applyShortDescriptionTextReplace()
    {
        if (!$this->quest_benefit) {
            $this->load('quest_benefit');
        }
        $replacer = [
            '%deals_title%' => '',
            '%voucher_qty%' => '',
            '%point_received%' => ''
        ];
        if ($this->quest_benefit->benefit_type == 'voucher') {
            if (!$this->quest_benefit->deals) {
                $this->quest_benefit->load('deals');
            }
            $replacer['%voucher_qty%'] = MyHelper::requestNumber($this->quest_benefit->value, '_POINT');
            $replacer['%deals_title%'] = $this->quest_benefit->deals->deals_title;
        } else {
            $replacer['%point_received%'] = MyHelper::requestNumber($this->quest_benefit->value, '_POINT');
        }
        $this->short_description = str_replace(array_keys($replacer), array_values($replacer), $this->short_description);
    }

    public function getContentsAttribute()
    {
        if (!$this->quest_benefit) {
            $this->load('quest_benefit');
        }
        $replacer = [
            '%deals_title%' => '',
            '%voucher_qty%' => '',
            '%point_received%' => ''
        ];
        if ($this->quest_benefit->benefit_type == 'voucher') {
            if (!$this->quest_benefit->deals) {
                $this->quest_benefit->load('deals');
            }
            $replacer['%voucher_qty%'] = MyHelper::requestNumber($this->quest_benefit->value, '_POINT');
            $replacer['%deals_title%'] = $this->quest_benefit->deals->deals_title;
        } else {
            $replacer['%point_received%'] = MyHelper::requestNumber($this->quest_benefit->value, '_POINT');
        }

        $result = $this->quest_contents->toArray();
        $result = QuestContent::where('id_quest', $this->id_quest)
            ->select('title', 'content')
            ->where('is_active', 1)
            ->orderBy('order')
            ->get()
            ->each(function ($item) use ($replacer) {
                $item->content = str_replace(array_keys($replacer), array_values($replacer), $item->content);
            })
            ->toArray();
        return $result;
    }

    public function getTextLabelAttribute($value = null)
    {
        $now = date('Y-m-d H:i:s');
        $date_start = MyHelper::indonesian_date_v2($this->date_start, $value ? 'd F Y, H:i' : 'd F Y');
        $date_end = MyHelper::indonesian_date_v2($this->date_end, $value ? 'd F Y, H:i' : 'd F Y');
        if ($this->claimed_status) {
            return [
                'text' => 'Selesai ' . MyHelper::indonesian_date_v2($this['redemption_date'], $value ? 'd F Y, H:i' : 'd F Y'),
                'code' => 2
            ];
        }
        if ($this->date_start > $now) {
            return [
                'text' => 'Dimulai pada ' . $date_start,
                'code' => 0,
            ];
        } elseif ($this->date_start <= $now && $this->date_end >= $now) {
            return [
                'text' => 'Berlaku sampai ' . $date_end,
                'code' => 1,
            ];
        } else {
            $stop_reason = $this->stop_reason ? $this->stop_reason : null;
            if ($stop_reason == 'voucher runs out') {
                $stop_reason = "\n" . 'karena hadiah sudah habis';
            } else {
                $stop_reason = $stop_reason ? "\n karena $stop_reason" : '';
            }
            return [
                'text' => 'Berakhir pada ' . $date_end,
                'stop_reason' => $stop_reason,
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
