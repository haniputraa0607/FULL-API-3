<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OutletPhoto
 * 
 * @property int $id_outlet_photo
 * @property int $id_outlet
 * @property string $outlet_photo
 * @property int $outlet_photo_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\Outlet $outlet
 *
 * @package App\Models
 */
class TransactionVoucher extends Model
{
	protected $primaryKey = 'id_transaction_voucher';
	protected $casts = [
		'id_transaction' => 'int',
		'id_deals_voucher' => 'int'
	];

	protected $fillable = [
		'id_transaction',
		'id_deals_voucher'
	];

	public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id_transaction');
	}
	
	public function deals_voucher()
    {
        return $this->belongsTo(DealsVoucher::class, 'id_deals_voucher', 'id_deals_voucher');
    }

}
