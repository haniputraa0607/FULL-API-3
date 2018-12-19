<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:17 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OauthAuthCode
 * 
 * @property string $id
 * @property int $user_id
 * @property int $client_id
 * @property string $scopes
 * @property bool $revoked
 * @property \Carbon\Carbon $expires_at
 *
 * @package App\Models
 */
class OauthAuthCode extends Model
{
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'client_id' => 'int',
		'revoked' => 'bool'
	];

	protected $dates = [
		'expires_at'
	];

	protected $fillable = [
		'user_id',
		'client_id',
		'scopes',
		'revoked',
		'expires_at'
	];
}
