<?php

namespace Modules\Xendit\Http\Controllers;

use App\Lib\MyHelper;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\TransactionGroup;
use Modules\Xendit\Lib\CustomHttpClient;
use Xendit\Xendit;
use DateTime;
use App\Http\Models\Transaction;
use DB;
use App\Http\Models\User;
use App\Http\Models\Configs;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Modules\Xendit\Entities\DealsPaymentXendit;
use Modules\Xendit\Entities\SubscriptionPaymentXendit;
use Illuminate\Http\Request;
use Modules\Xendit\Entities\LogXendit;
use Modules\Subscription\Entities\SubscriptionUser;
use App\Http\Models\DealsUser;
use App\Http\Models\Outlet;

class XenditController extends Controller
{
    public function __construct()
    {
        $this->callback_url = env('XENDIT_CALLBACK_URL', route('notif_xendit'));
        $this->autocrm             = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->redirect_url = env(optional(request()->user())->tokenCan('apps') ? 'XENDIT_REDIRECT_URL_NATIVE' : 'XENDIT_REDIRECT_URL');

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

    protected function getUniversalStatusCode($status) {
        $universalStatus = [
            'FAILED' => 'FAILED', // OVO & LINKAJA
            'COMPLETED' => 'COMPLETED', // OVO
            'EXPIRED' => 'FAILED', // DANA
            'PAID' => 'COMPLETED', // DANA
            'SUCCESS_COMPLETED' => 'COMPLETED', // LINKAJA
            'PENDING' => 'PENDING', // LINKAJA
            'REQUEST_RECEIVED' => 'PENDING', //LINKAJA
        ];

        return $universalStatus[$status] ?? 'UNKNOWN';
    }

    public function notif(Request $request)
    {
        $header         = $request->header();
        $validToken     = $this->callback_token;
        $update         = 0;
        $cat            = $header['x-callback-token'][0] ?? null;

        if ($request->ewallet_type == 'OVO') {
            $validToken = null;
        }

        if ($validToken && $cat != $validToken) {
            $status_code = 401;
            $response    = [
                'status'   => 'fail',
                'messages' => ['Invalid token'],
            ];
            goto end;
        }

        // merge with checkstatus
        $checkStatus = $this->checkStatus($request->id, $request->ewallet_type, $errors);
        if (is_array($checkStatus)) {
            $request->merge($checkStatus);
        } else {
            \Log::error('Something wrong while checking xendit payment status.', $errors);
        }
        $post = $request->post();
        $universalStatus = $this->getUniversalStatusCode($request->status ?: $request->payment_status);

        if ($universalStatus == 'PENDING') {
            $status_code = 422;
            $response    = ['status' => 'fail', 'messages' => ['Payment PENDING']];
            goto end;
        }

        DB::beginTransaction();

        if (stristr($post['external_id'], "TRX")) {
            $trx = TransactionGroup::where('transaction_receipt_number', $post['external_id'])->join('transaction_payment_xendits', 'transaction_groups.id_transaction_group', '=', 'transaction_payment_xendits.id_transaction_group')->first();

            if (!$trx) {
                $status_code = 404;
                $response    = ['status' => 'fail', 'messages' => ['Transaction not found']];
                goto end;
            }
            if ($trx->amount != $post['amount']) {
                $status_code = 422;
                $response    = ['status' => 'fail', 'messages' => ['Invalid amount']];
                goto end;
            }

            if ($universalStatus == 'COMPLETED') {
                $update                 = $trx->triggerPaymentCompleted([
                    'amount' => $post['amount'] / 100,
                ]);
            } elseif ($universalStatus == 'FAILED') {
                $update                 = $trx->triggerPaymentCancelled();
            }

            if (!$update) {
                DB::rollBack();
                if ($universalStatus == 'FAILED') {
                    $status_code = 200;
                    $response    = [
                        'status'   => 'success',
                        'messages' => ['Payment expired'],
                    ];
                } else {
                    $status_code = 500;
                    $response    = [
                        'status'   => 'fail',
                        'messages' => ['Failed update payment status'],
                    ];
                }
                goto end;
            }

            TransactionPaymentXendit::where('id_transaction_group', $trx->id_transaction_group)->update([
                'status'         => $universalStatus,
                'xendit_id'      => $post['id'] ?? null,
                'payment_id'     => $post['payment_id'] ?? null,
                'expiration_date'=> $post['expiration_date'] ?? null,
                'failure_code'   => $post['failure_code'] ?? null,
            ]);
            DB::commit();

            $status_code = 200;
            $response    = ['status' => 'success'];
        } elseif (stristr($post['external_id'], 'SUBS')) {
            $subs_payment = SubscriptionPaymentXendit::where('order_id', $post['external_id'])->join('subscriptions', 'subscriptions.id_subscription', '=', 'subscription_payment_xendits.id_subscription')->first();

            if (!$subs_payment) {
                $status_code = 404;
                $response    = ['status' => 'fail', 'messages' => ['Subscription not found']];
                goto end;
            }
            if ($subs_payment->amount != $post['amount']) {
                $status_code = 422;
                $response    = ['status' => 'fail', 'messages' => ['Invalid amount']];
                goto end;
            }

            $subscriptionUser = SubscriptionUser::where('id_subscription_user', $subs_payment->id_subscription_user)->first();

            if ($universalStatus == 'COMPLETED') {
                $update = $subscriptionUser->complete();
            } elseif ($universalStatus == 'FAILED') {
                $update = $subscriptionUser->cancel();
            }

            if (!$update) {
                DB::rollBack();
                $status_code = 500;
                $response    = [
                    'status'   => 'fail',
                    'messages' => ['Failed update payment status'],
                ];
                goto end;
            }

            $subs_payment->update([
                'status'         => $universalStatus,
                'xendit_id'      => $post['id'] ?? null,
                'payment_id'     => $post['payment_id'] ?? null,
                'expiration_date'=> $post['expiration_date'] ?? null,
                'failure_code'   => $post['failure_code'] ?? null,
            ]);
            DB::commit();

            $userPhone = User::select('phone')->where('id', $subs_payment->id_user)->pluck('phone')->first();
            $send      = app($this->autocrm)->SendAutoCRM(
                'Buy Paid Subscription Success',
                $userPhone,
                [
                    'subscription_title'   => $subs_payment->subscription_title,
                    'id_subscription_user' => $subs_payment->id_subscription_user,
                ]
            );
            $status_code = 200;
            $response    = ['status' => 'success'];
        } else {
            $deals_payment = DealsPaymentXendit::where('order_id', $post['external_id'])->join('deals', 'deals.id_deals', '=', 'deals_payment_xendits.id_deals')->first();

            if (!$deals_payment) {
                $status_code = 404;
                $response    = ['status' => 'fail', 'messages' => ['Transaction not found']];
                goto end;
            }
            if ($deals_payment->amount != $post['amount']) {
                $status_code = 422;
                $response    = ['status' => 'fail', 'messages' => ['Invalid amount']];
                goto end;
            }

            $dealsUser = DealsUser::where('id_deals_user', $deals_payment->id_deals_user)->first();
            if ($universalStatus == 'COMPLETED') {
                $update = $dealsUser->complete();
            } elseif ($universalStatus == 'FAILED') {
                $update = $dealsUser->cancel();
            }

            if (!$update) {
                DB::rollBack();
                $status_code = 500;
                $response    = [
                    'status'   => 'fail',
                    'messages' => ['Failed update payment status'],
                ];
                goto end;
            }

            $deals_payment->update([
                'status'         => $universalStatus,
                'xendit_id'      => $post['id'] ?? null,
                'payment_id'     => $post['payment_id'] ?? null,
                'expiration_date'=> $post['expiration_date'] ?? null,
                'failure_code'   => $post['failure_code'] ?? null,
            ]);
            DB::commit();

            $userPhone = User::select('phone')->where('id', $deals_payment->id_user)->pluck('phone')->first();
            $send      = app($this->autocrm)->SendAutoCRM(
                'Payment Deals Success',
                $userPhone,
                [
                    'deals_title'   => $deals_payment->title,
                    'id_deals_user' => $deals_payment->id_deals_user,
                ]
            );
            $status_code = 200;
            $response    = ['status' => 'success'];
        }

        end:
        try {
            LogXendit::create([
                'type'                 => 'webhook',
                'id_reference'         => $post['external_id'],
                'request'              => json_encode($post),
                'request_url'          => url(route('notif_xendit')),
                'request_header'       => json_encode($header),
                'response'             => json_encode($response),
                'response_status_code' => $status_code,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed write log to LogXendit: ' . $e->getMessage());
        }
        return response()->json($response, $status_code);
    }

    public function cronCancel()
    {
        $log = MyHelper::logCron('Update Transaction Status Xendit');
        $result = [
            'found' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'pending' => 0,
            'invalid' => 0,
            'errors' => [],
        ];
        try {
            $expired = date('Y-m-d H:i:s', time() - 1200);
            $transactions = Transaction::join('transaction_payment_xendits', 'transaction_payment_xendits.id_transaction', 'transactions.id_transaction')
                ->where('transaction_date', '<=', $expired)
                ->where('transaction_payment_status', 'Pending')
                ->get();
            $result['found'] = $transactions->count();
            foreach ($transactions as $transaction) {
                $errors = [];
                $status = $this->checkStatus($transaction->xendit_id, $transaction->type, $errors);
                if ($status) {
                    $universalStatus = $this->getUniversalStatusCode($status['status']);
                    if ($universalStatus == 'FAILED') {
                        $transaction->cancel();
                        $result['cancelled']++;
                    } elseif ($universalStatus == 'COMPLETED') {
                        if ($status['amount'] != $transaction->amount) {
                            $result['invalid']++;
                            $result['errors'][] = "Invalid amount for {$transaction->transaction_receipt_number}";
                            continue;
                        }
                        $transaction->complete();
                        $result['completed']++;
                    } else {
                        $result['pending']++;
                        continue;
                    }
                    TransactionPaymentXendit::where('id_transaction', $transaction->id_transaction)->update([
                        'status'         => $universalStatus,
                        'xendit_id'      => $status['id'] ?? $transaction->xendit_id,
                        'expiration_date'=> $status['expiration_date'] ?? $transaction->expiration_date,
                        'failure_code'   => $status['failure_code'] ?? $transaction->failure_code,
                    ]);
                } else {
                    $result['errors'] = $errors;
                }
            }
            $log->success($result);
            return response()->json($result);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function cronCancelDeals()
    {
        $log = MyHelper::logCron('Update Deals Status Xendit');
        $result = [
            'found' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'pending' => 0,
            'invalid' => 0,
            'errors' => [],
        ];
        try {
            $expired = date('Y-m-d H:i:s', time() - 1200);
            $deals_users = DealsUser::where('paid_status', 'Pending')
                ->join('deals_payment_xendits', 'deals_users.id_deals_user', '=', 'deals_payment_xendits.id_deals_user')
                ->where('payment_method', 'Xendit')
                ->where('claimed_at', '<=', $expired)
                // ->where('claimed_at', '<=', $check_success)
                ->with(['user'])
                ->get();

            $result['found'] = $deals_users->count();
            foreach ($deals_users as $deals_user) {
                $errors = [];
                $status = $this->checkStatus($deals_user->xendit_id, $deals_user->type, $errors);
                if ($status) {
                    $universalStatus = $this->getUniversalStatusCode($status['status']);
                    if ($universalStatus == 'FAILED') {
                        $deals_user->cancel();
                        $result['cancelled']++;
                    } elseif ($universalStatus == 'COMPLETED') {
                        if ($status['amount'] != $deals_user->amount) {
                            $result['invalid']++;
                            $result['errors'][] = "Invalid amount for {$deals_user->order_id}";
                            continue;
                        }
                        $deals_user->complete();
                        $result['completed']++;
                    } else {
                        $result['pending']++;
                        continue;
                    }
                    DealsPaymentXendit::where('id_deals_user', $deals_user->id_deals_user)->update([
                        'status'         => $universalStatus,
                        'xendit_id'      => $status['id'] ?? $deals_user->xendit_id,
                        'expiration_date'=> $status['expiration_date'] ?? $deals_user->expiration_date,
                        'failure_code'   => $status['failure_code'] ?? $deals_user->failure_code,
                    ]);
                } else {
                    $result['errors'] = $errors;
                }
            }
            $log->success($result);
            return response()->json($result);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function cronCancelSubscription()
    {
        $log = MyHelper::logCron('Update Subscription Status Xendit');
        $result = [
            'found' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'pending' => 0,
            'invalid' => 0,
            'errors' => [],
        ];
        try {
            $expired = date('Y-m-d H:i:s', time() - 1200);
            $subscription_users = SubscriptionUser::where('paid_status', 'Pending')
                ->join('subscription_payment_xendits', 'subscription_users.id_subscription_user', '=', 'subscription_payment_xendits.id_subscription_user')
                ->where('payment_method', 'Xendit')
                ->where('bought_at', '<=', $expired)
                ->with(['user'])
                ->get();

            $result['found'] = $subscription_users->count();
            foreach ($subscription_users as $subscription_user) {
                $errors = [];
                $status = $this->checkStatus($subscription_user->xendit_id, $subscription_user->type, $errors);
                if ($status) {
                    $universalStatus = $this->getUniversalStatusCode($status['status']);
                    if ($universalStatus == 'FAILED') {
                        $subscription_user->cancel();
                        $result['cancelled']++;
                    } elseif ($universalStatus == 'COMPLETED') {
                        if ($status['amount'] != $subscription_user->amount) {
                            $result['invalid']++;
                            $result['errors'][] = "Invalid amount for {$subscription_user->order_id}";
                            continue;
                        }
                        $subscription_user->complete();
                        $result['completed']++;
                    } else {
                        $result['pending']++;
                        continue;
                    }
                    SubscriptionPaymentXendit::where('id_subscription_user', $subscription_user->id_subscription_user)->update([
                        'status'         => $universalStatus,
                        'xendit_id'      => $status['id'] ?? $subscription_user->xendit_id,
                        'expiration_date'=> $status['expiration_date'] ?? $subscription_user->expiration_date,
                        'failure_code'   => $status['failure_code'] ?? $subscription_user->failure_code,
                    ]);
                } else {
                    $result['errors'] = $errors;
                }
            }
            $log->success($result);
            return response()->json($result);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
    }

    public function create($method, $external_id, $amount, $options = [], &$errors = [])
    {
        CustomHttpClient::setLogType('create');
        CustomHttpClient::setIdReference($external_id);
        $method = strtoupper($method);

        $redirect_url = str_replace(
            ['%order_id%', '%type%'],
            [urlencode($options['order_id'] ?? $external_id), $options['type'] ?? 'trx'],
            $this->redirect_url
        );

        $params = [
            'external_id'  => (string) $external_id,
            'amount'       => (int) $amount,
            'success_redirect_url' => $redirect_url,
            'payment_methods' => [$method],
            'items'        => $options['items'] ?? [],
        ];

        try {
            // switch ($method) {
            //     case 'OVO':
            //         $params['ewallet_type'] = 'OVO';
            //         break;

            //     case 'DANA':
            //         $validity_period = (int) MyHelper::setting('xendit_validity_period', 'value', 300);
            //         $params['ewallet_type'] = 'DANA';
            //         $params['expiration_date'] = (new DateTime("+ $validity_period seconds"))->format('c');
            //         break;

            //     case 'LINKAJA':
            //         $params['ewallet_type'] = 'LINKAJA';
            //         $params['items'] = $options['items'] ?? [];
            //         break;
            //     default:
            //         throw new \Exception('Invalid payment method');
            //         break;
            // }
            $result = \Xendit\Invoice::create($params);
            return $result;
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            return false;
        }
    }

    public function checkStatus($id, $ewallet_type, &$errors = [])
    {
        CustomHttpClient::setLogType('check_status');
        CustomHttpClient::setIdReference($id);
        try {
            return \Xendit\Invoice::retrieve($id);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            return false;
        }
    }

    public function expireInvoice($id, &$errors = []){
        CustomHttpClient::setLogType('expire_invoice');
        CustomHttpClient::setIdReference($id);
        try {
            return \Xendit\Invoice::expireInvoice($id);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            return false;
        }
    }

    public function refund($reference, $type = 'trx', &$errors = null, &$refund_reference_id = null)
    {
        $data = [
            'payment_reference_id' => '',
        ];
        $params = [
            'for-user-id'  => null,
        ];
        switch ($type) {
            case 'trx':
                if (is_numeric($reference)) {
                    $reference = Transaction::where('id_transaction', $reference)->with('outlet')->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['transaction_receipt_number'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $params['for-user-id'] = optional($reference->outlet->xendit_account)->xendit_id;
                $payment = TransactionPaymentXendit::where('id_transaction', $reference['id_transaction'])->first();
                if (!in_array(strtolower($payment->type), ['ovo', 'dana', 'shopeepay', 'linkaja'])) {
                    $errors = ['Refund not supported dor this payment type'];
                    return false;
                }
                $data['payment_reference_id'] = $reference['transaction_receipt_number'];
                $data['payment_id'] = $payment['payment_id'];
                break;

            case 'deals':
                if (is_numeric($reference)) {
                    $reference = DealsPaymentXendit::where('id_deals_user', $reference)->first();
                    if (!$reference) {
                        $errors = ['Transaction not found'];
                        return false;
                    }
                } else {
                    if (!($reference['order_id'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['order_id'];
                $data['payment_id'] = $reference['payment_id'];
                break;

            case 'subscription':
                if (is_numeric($reference)) {
                    $reference = SubscriptionPaymentXendit::where('id_subscription_user', $reference)->first();
                    if (!$reference) {
                        $errors = ['Subscription not found'];
                        return false;
                    }
                } else {
                    if (!($reference['order_id'] ?? false)) {
                        $errors = ['Invalid reference'];
                        return false;
                    }
                }
                $data['payment_reference_id'] = $reference['order_id'];
                $data['payment_id'] = $reference['payment_id'];
                break;

            default:
                # code...
                break;
        }
        try {
            CustomHttpClient::setLogType('refund');
            CustomHttpClient::setIdReference($data['payment_reference_id']);
            \Xendit\EWallets::refundEwalletCharge($data['payment_id'], $params);
            return true;
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            return false;
        }
    }
}
