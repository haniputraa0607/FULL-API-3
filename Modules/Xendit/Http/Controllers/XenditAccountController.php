<?php

namespace Modules\Xendit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Xendit\Lib\CustomHttpClient;
use App\Lib\MyHelper;
use GuzzleHttp\Client as Guzzle;
use Xendit\Xendit;
use Xendit\Platform;
use App\Http\Models\Outlet;
use Modules\Xendit\Entities\XenditAccount;

class XenditAccountController extends Controller
{
    public function __construct()
    {
        Xendit::setApiKey($this->key);
        Xendit::setHttpClient(
            new CustomHttpClient(
                new Guzzle(
                    [
                        'base_uri' => Xendit::$apiBase,
                        'verify'   => false,
                        'timeout'  => 60,
                    ]
                )
            )
        );
    }

    public function __get($key)
    {
        return env('XENDIT_' . strtoupper($key));
    }

    public function index(Request $request)
    {
        $xenditAccounts = (new XenditAccount)->newQuery();

        if ($request->for_datatable) {
            $pagination = $xenditAccounts->paginate()->toArray();
            return [
                'draw' => $request->draw,
                'recordsTotal' => $pagination['total'],
                'recordsFiltered' => $pagination['total'],
                'data' => $pagination['data'],
            ];
        }

        return MyHelper::checkGet($xenditAccounts->get());
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $xenditAccount = XenditAccount::where(['xendit_id' => $request->xendit_id])->orWhere(['id_xendit_account' => $request->id_xendit_account])->first();
        return MyHelper::checkGet($xenditAccount->toArray());
    }

    public function update(Request $request)
    {
        try {
            if (!$request->xendit_id) {
                if ($request->id_outlet) {
                    Outlet::where('id_outlet', $request->id_outlet)->update(['id_xendit_account' => null]);
                    return [
                        'status' => 'success',
                        'result' => null,
                    ];
                }

                return [
                    'status' => 'fail',
                    'messages' => ['Please fill xendit account id'],
                ];
            }
            $account = Platform::getAccount($request->xendit_id);
            $xenditAccount = XenditAccount::updateOrCreate(['xendit_id' => $request->xendit_id], $account);
            if ($xenditAccount) {
                if ($request->id_outlet) {
                    Outlet::where('id_outlet', $request->id_outlet)->update(['id_xendit_account' => $xenditAccount->id_xendit_account]);
                }
                return [
                    'status' => 'success',
                    'result' => $xenditAccount,
                ];
            } else {
                return [
                    'status' => 'fail',
                    'messages' => ['Failed create record'],
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'messages' => [$e->getMessage()],
            ];
        }
    }
}
