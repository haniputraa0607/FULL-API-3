<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 25 Mar 2021 16:29:59 +0700.
 */

namespace Modules\Disburse\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class LogEditBankAccount
 *
 * @property int $id_log_edit_bank_account
 * @property \Carbon\Carbon $date_time
 * @property int $id_user
 * @property int $id_user_franchise
 * @property int $id_bank_account
 * @property int $id_outlet
 * @property string $id_outlet_old
 * @property string $id_outlet_new
 * @property int $id_bank_name_old
 * @property int $id_bank_name_new
 * @property string $beneficiary_name_old
 * @property string $beneficiary_name_new
 * @property string $beneficiary_account_old
 * @property string $beneficiary_account_new
 * @property string $beneficiary_alias_old
 * @property string $beneficiary_alias_new
 * @property string $beneficiary_email_old
 * @property string $beneficiary_email_new
 * @property string $action
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Disburse\Entities
 */
class LogEditBankAccount extends Eloquent
{
    protected $primaryKey = 'id_log_edit_bank_account';

    protected $casts = [
        'id_user' => 'int',
        'id_user_franchise' => 'int',
        'id_bank_account' => 'int',
        'id_outlet' => 'int',
        'id_bank_name_old' => 'int',
        'id_bank_name_new' => 'int'
    ];

    protected $dates = [
        'date_time'
    ];

    protected $fillable = [
        'date_time',
        'id_user',
        'id_user_franchise',
        'id_bank_account',
        'id_outlet',
        'id_outlet_old',
        'id_outlet_new',
        'id_bank_name_old',
        'id_bank_name_new',
        'beneficiary_name_old',
        'beneficiary_name_new',
        'beneficiary_account_old',
        'beneficiary_account_new',
        'beneficiary_alias_old',
        'beneficiary_alias_new',
        'beneficiary_email_old',
        'beneficiary_email_new',
        'action'
    ];
}
