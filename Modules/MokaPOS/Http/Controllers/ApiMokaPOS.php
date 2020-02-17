<?php

namespace Modules\MokaPOS\Http\Controllers;

use App\Http\Models\City;
use App\Http\Models\LogBackendError;
use App\Http\Models\Outlet;
use App\Http\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MokaPOS\Entities\MokaAccount;
use Modules\MokaPOS\Entities\MokaAccountBusiness;
use Modules\MokaPOS\Jobs\SyncOutlet;
use Modules\MokaPOS\Jobs\SyncProductOutlet;

class ApiMokaPOS extends Controller
{
    function curlMoka($data, $url, $header, $request)
    {
        if (is_null($header)) {
            $header = [
                'Content-Type: application/json'
            ];
        }

        $curlHandle = curl_init(env('URL_MOKA_POS') . $url);

        switch ($request) {
            case 'POST':
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
                break;
            case 'GET':
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "GET");
                break;
        }

        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'cURL');
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);
        $hasil = curl_exec($curlHandle);
        curl_close($curlHandle);
        $hasil = json_decode($hasil, true);
        if ($hasil['meta']['code'] != 200) {
            $hasil = null;
        }

        return $hasil;
    }

    function setAuthToken($data)
    {
        $dt = json_encode([
            "client_id"     => $data['application_id'],
            "client_secret" => $data['secret'],
            "code"          => $data['code'],
            "grant_type"    => "authorization_code",
            "redirect_uri"  => $data['redirect_url']
        ]);

        $auth = self::curlMoka($dt, '/oauth/token', null, 'POST');
        if (is_null($auth)) {
            return [
                'status'    => 'fail',
                'message'   => 'Failed at connect to MokaPOS'
            ];
        }

        try {
            MokaAccount::where('id_moka_account', $data['id_moka_account'])->update([
                'token'         => $auth['access_token'],
                'refresh_token' => $auth['refresh_token']
            ]);
            $data = MokaAccount::where('id_moka_account', $data['id_moka_account'])->first();
            $result = [
                'status'    => 'success',
                'data'      => $data->attributesToArray()
            ];
        } catch (\Exception $e) {
            LogBackendError::logExceptionMessage("ApiMokaPOS/setAuthToken=>" . $e->getMessage(), $e);
            $result = [
                'status'    => 'fail',
                'message'   => 'Failed insert data or Failed at connect to MokaPOS'
            ];
        }

        return $result;
    }

    public function syncBusiness()
    {
        $getAllAccount = MokaAccount::get()->toArray();

        $failedBusiness     = [];
        $successBusiness    = [];
        foreach ($getAllAccount as $valueAcc) {
            if (empty($valueAcc['token']) && empty($valueAcc['refresh_token'])) {
                $setAuthToken = self::setAuthToken($valueAcc);
                if ($setAuthToken['status'] != 'success') {
                    $failedBusiness[] = 'Failed to sync, business account ' . $valueAcc['name'];
                    continue;
                }
                $valueAcc = $setAuthToken['data'];
            }

            $getData = self::curlMoka(null, '/v1/businesses', ['Authorization: Bearer ' . $valueAcc['token']], 'GET');
            if (is_null($getData)) {
                $failedBusiness[] = 'Failed at sync, get business account ' . $valueAcc['name'];
                continue;
            }

            foreach ($getData['data'] as $valueBusiness) {
                try {
                    MokaAccountBusiness::updateOrCreate(
                        [
                            'id_moka_account'   => $valueAcc['id_moka_account'],
                            'id_moka_business'  => $valueBusiness['id']
                        ],
                        [
                            'id_moka_account'   => $valueAcc['id_moka_account'],
                            'id_moka_business'  => $valueBusiness['id'],
                            'name'              => $valueBusiness['name'],
                            'email'             => $valueBusiness['email'],
                            'phone'             => $valueBusiness['phone']
                        ]
                    );
                    $successBusiness[] = 'Success to sync, business account ' . $valueAcc['name'] . ' at business ' . $valueBusiness['name'];
                } catch (\Exception $e) {
                    LogBackendError::logExceptionMessage("ApiMokaPOS/syncBusiness=>" . $e->getMessage(), $e);
                    $failedBusiness[] = 'fail to sync, business account ' . $valueAcc['name'] . ' at business ' . $valueBusiness['name'];
                    continue;
                }
            }
        }

        return [
            'status'    => 'success',
            'result'    => [
                'success sync'  => $successBusiness,
                'failed sync'   => $failedBusiness
            ]
        ];
    }

    public function syncOutlet()
    {
        $syncBusiness = self::syncBusiness();
        if ($syncBusiness['status'] != 'success') {
            return [
                'status'    => 'fail',
                'message'   => 'Failed to sync, business account'
            ];
        }

        $failedOutlet     = [];
        $successOutlet    = [];
        $getAccount = MokaAccount::with('moka_account_business')->get()->toArray();
        foreach ($getAccount as $valueAccount) {
            foreach ($valueAccount['moka_account_business'] as $keyBusiness => $valueBusiness) {
                $getOutlet = self::curlMoka(null, '/v1/businesses/' . $valueBusiness['id_moka_business'] . '/outlets', ['Authorization: Bearer ' . $valueAccount['token']], 'GET');
                if (is_null($getOutlet)) {
                    $failedOutlet[] = 'Failed at sync, get business account ' . $valueAccount['name'] . ' at bussiness ' . $valueBusiness['name'];
                    continue;
                }

                $data = [];
                foreach ($getOutlet['data']['outlets'] as $keyOutlet => $valueOutlet) {
                    $checkProvince  = Province::where('province_name', $valueOutlet['province'])->first();
                    if (!$checkProvince) {
                        $failedOutlet[] = 'Failed sync outlet, ' . $valueOutlet['province'] . ' province not available at system!';
                        continue;
                    }
                    $checkCities    = City::where('city_name', $valueOutlet['city'])->first();
                    if (!$checkCities) {
                        $failedOutlet[] = 'Failed sync outlet, ' . $valueOutlet['city'] . ' cities not available at system!';
                        continue;
                    }

                    $data[$keyOutlet]['outlet'] = [
                        'id_moka_account_business'  => $valueBusiness['id_moka_account_business'],
                        'id_moka_outlet'            => $valueOutlet['id'],
                        'outlet_code'               => $valueOutlet['id'],
                        'outlet_name'               => $valueOutlet['name'],
                        'outlet_address'            => $valueOutlet['address'],
                        'id_city'                   => $checkCities->id_city,
                        'outlet_postal_code'        => $valueOutlet['postal_code'],
                        'outlet_phone'              => $valueOutlet['phone_number'],
                        'outlet_latitude'           => $valueOutlet['latitude'],
                        'outlet_longitude'          => $valueOutlet['longitude'],
                        'outlet_status'             => ($valueOutlet['is_paying'] == true) ? 'Active' : 'Inactive'
                    ];

                    $data[$keyOutlet]['moka_outlet'] = [
                        'id_moka_account'           => $valueAccount['id_moka_account'],
                        'id_moka_outlet'            => $valueOutlet['id']
                    ];
                }

                SyncOutlet::dispatch(json_encode($data));
                $successOutlet[] = 'Success sync outlet, at business ' . $valueBusiness['name'];
            }
        }

        return [
            'status'    => 'success',
            'result'    => [
                'success sync'  => $successOutlet,
                'failed sync'   => $failedOutlet
            ]
        ];
    }

    public function syncProduct()
    {
        $syncBusiness = self::syncBusiness();
        if ($syncBusiness['status'] != 'success') {
            return [
                'status'    => 'fail',
                'message'   => 'Failed to sync, business account'
            ];
        }

        $failedProduct     = [];
        $successProduct    = [];
        $getAccount = MokaAccount::with('moka_outlet')->get()->toArray();
        foreach ($getAccount as $valueAccount) {
            foreach ($valueAccount['moka_outlet'] as $valueOutlet) {
                $getProduct = self::curlMoka(null, '/v1/outlets/' . $valueOutlet['id_moka_outlet'] . '/items', ['Authorization: Bearer ' . $valueAccount['token']], 'GET');
                if (is_null($getProduct)) {
                    $failedProduct[] = 'Failed at sync, get account ' . $valueAccount['name'];
                    continue;
                }
                foreach ($getProduct['data']['items'] as $keyProduct => $valueProduct) {
                    $data[$keyProduct] = [];
                    foreach ($valueProduct['item_variants'] as $valueVariant) {
                        $data[$keyProduct]['products'] = [
                            'product_code'          => $valueVariant['id'],
                            'product_name'          => implode(' ', [$valueProduct['name'], $valueVariant['name']]),
                            'product_name_pos'      => implode(' ', [$valueProduct['name'], $valueVariant['name']])
                        ];
                        $data[$keyProduct]['product_prices'] = [
                            'id_outlet'             => $valueOutlet['id_outlet'],
                            'product_price'         => $valueVariant['price'],
                            'product_status'        => 'Active',
                            'product_stock_status'  => ($valueVariant['in_stock'] == 0) ? 'Sold Out' : 'Availabe',
                        ];
                    }
                }
                SyncProductOutlet::dispatch(json_encode($data));
                $successProduct[] = 'Success sync outlet ' . $valueOutlet['id_moka_outlet'] . ', at account ' . $valueAccount['name'];
            }
        }

        return [
            'status'    => 'success',
            'result'    => [
                'success sync'  => $successProduct,
                'failed sync'   => $failedProduct
            ]
        ];
    }
}
