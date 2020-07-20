<?php

namespace Modules\UserFeedback\Entities;

use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
	protected $primaryKey = 'id_user_feedback';
	protected $table = 'user_feedbacks';
    protected $fillable = [
    	'id_outlet',
    	'id_user',
    	'id_transaction',
    	'rating_value',
        'rating_item_text',
    	'notes',
    	'image'
    ];
    public function transaction()
    {
        return $this->belongsTo(\App\Http\Models\Transaction::class,'id_transaction','id_transaction');
    }
    public function outlet()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class,'id_outlet','id_outlet');
    }
    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class,'id_user','id');
    }
}
