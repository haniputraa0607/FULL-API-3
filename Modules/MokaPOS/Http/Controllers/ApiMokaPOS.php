<?php

namespace Modules\MokaPOS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MokaPOS\Entities\MokaAccount;

class ApiMokaPOS extends Controller
{
    function setAuthToken($data)
    {
        $dt = json_encode([
            "client_id"     => $data['application_id'],
            "client_secret" => $data['secret'],
            "code"          => $data['code'],
            "grant_type"    => "authorization_code",
            "redirect_uri"  => $data['redirect_url']
        ]);
        $curlHandle = curl_init(env('URL_MOKA_POS') . "/oauth/token");
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $dt);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $hasil = curl_exec($curlHandle);
        curl_close($curlHandle);

        $hasil = json_decode($hasil, true);

        MokaAccount::where('id_moka_account', $data['id_moka_account'])->update([
            'token'         => $hasil['access_token'],
            'refresh_token' => $hasil['refresh_token']
        ]);

        return $hasil;
    }

    public function syncBusiness()
    {
        $getAllAccount = MokaAccount::get()->toArray();

        foreach ($getAllAccount as $keyAcc => $valueAcc) {
            if (empty($valueAcc['token']) && empty($valueAcc['token'])) {
                $setAuthToken = self::setAuthToken($valueAcc);
                dd($setAuthToken);
            }
        }
        dd($getAllAccount);

        return view('pos::index');
    }
}
