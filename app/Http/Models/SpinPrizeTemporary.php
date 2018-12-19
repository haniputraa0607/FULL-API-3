<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SpinPrizeTemporary extends Model
{
    protected $table = 'spin_prize_temporary';
    protected $primaryKey = 'id_spin_prize_temporary';

	protected $fillable = [
		'id_deals',
		'id_user'
	];

	public function deals()
	{
		return $this->hasOne(Deal::class, 'id_deals', 'id_deals');
	}
}
