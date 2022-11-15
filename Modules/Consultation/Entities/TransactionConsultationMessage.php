<?php

namespace Modules\Consultation\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionConsultationMessage extends Model
{
    protected $table = 'transaction_consultation_messages';
    protected $primaryKey = 'id_transaction_consultation_message';

    protected $fillable = [
        'id_transaction_consultation',
        'id_message',
        'direction',
        'content_type',
        'text',
        'url',
        'caption',
        'created_at_infobip'
    ];

    public function getTimeAttribute()
    {
        return date('H:i', strtotime($this->created_at_infobip));
    }
}
