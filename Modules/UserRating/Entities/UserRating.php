<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRating extends Model
{
    protected $primaryKey = 'id_user_rating';
    protected $fillable = ['id_user','id_transaction', 'id_transaction_consultation', 'id_transaction_product_service','id_outlet','id_product','id_doctor','option_question','rating_value','suggestion','option_value','is_anonymous'];
    public function transaction()
    {
        return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction', 'id_transaction');
    }
    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'id_user', 'id');
    }

    public function doctor()
    {
        return $this->belongsTo(\Modules\Doctor\Entities\Doctor::class, 'id_doctor');
    }

    public function product()
    {
        return $this->belongsTo(\App\Http\Models\Product::class, 'id_product');
    }
}
