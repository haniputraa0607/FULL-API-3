<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';
	protected $primaryKey = 'id_bank_account';

	protected $fillable = [
	    'id_bank_name',
		'beneficiary_name',
        'beneficiary_account',
        'beneficiary_alias',
        'beneficiary_email'
	];

    public function bank_account_outlet()
    {
        return $this->hasMany(\Modules\Disburse\Entities\BankAccountOutlet::class, 'id_bank_account')
            ->join('outlets', 'outlets.id_outlet', 'bank_account_outlets.id_outlet');
    }
}
