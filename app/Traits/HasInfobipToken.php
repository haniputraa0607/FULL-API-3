<?php

namespace App\Traits;

use App\Lib\Infobip;

trait HasInfobibToken {
    public function infobipTokens()
    {
        return $this->morphMany('App\Http\Models\InfobipRtcToken', 'tokenable');
    }

    public function getActiveToken()
    {
    	$token = $this->infobipTokens()->orderBy('expired_at', 'desc')->where('expired_at', '>', date('Y-m-d H:i:s', time() + 3600))->first();
    	if ($token) {
    		return $token->token;
    	}
    	return Infobip::generateInfobibToken($this);
    }
}