<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Transaction;
use App\Http\Models\DealsUser;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;

use App\Lib\MyHelper;

class ApiHistoryController extends Controller
{
    public function historyTrx(Request $request) {

        $post = $request->json()->all();
        // return $post;
        $id = $request->user()->id;
        $order = 'new';
        $page = 0;

        $transaction = $this->transaction($post, $id);
        $voucher = [];

        if (is_null($post['pickup_order']) && is_null($post['delivery_order']) && is_null($post['offline_order'])) {
            if (!is_null($post['buy_voucher'])) {
                $transaction = [];
            }
            
            $voucher = $this->voucher($post, $id);
        } elseif (!is_null($post['pickup_order']) || !is_null($post['delivery_order']) || !is_null($post['offline_order'])) {
            if (!is_null($post['buy_voucher'])) {
                $voucher = $this->voucher($post, $id);
            }
        }

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;
        
        $merge = array_merge($transaction, $voucher);
        $sortTrx = $this->sorting($merge, $order, $page);

        $check = MyHelper::checkGet($sortTrx);
        if (count($merge) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortTrx['data'];
            $ampas['total']         = count($merge);
            $ampas['next_page_url'] = null;

            if ($sortTrx['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history-trx?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
    }

    public function historyPoint(Request $request) {
        $post = $request->json()->all();
        $id = $request->user()->id;
        $order = 'new';
        $page = 0;

        $post['id'] = $id;

        if (!is_null($post['oldest'])) {
            $order = 'old';
        }

        if (!is_null($post['newest'])) {
            $order = 'new';
        }

        if (!is_null($request->get('page'))) {
            $page = $request->get('page');
        }

        $next_page = $page + 1;

        $point = $this->point($post);

        $sortPoint = $this->sorting($point, $order, $page);
        
        $check = MyHelper::checkGet($sortPoint);
        if (count($point) > 0) {
            $ampas['status'] = 'success';
            $ampas['current_page']  = $page;
            $ampas['data']          = $sortPoint['data'];
            $ampas['total']         = count($point);
            $ampas['next_page_url'] = null;

            if ($sortPoint['status'] == true) {
                $ampas['next_page_url'] = ENV('APP_API_URL').'/api/transaction/history-point?page='.$next_page;
            }
        } else {
            $ampas['status'] = 'fail';
            $ampas['messages'] = ['empty'];
            
        }

        return response()->json($ampas);
     
               
    }

    public function sorting($data, $order, $page) {
        $date = [];
        foreach ($data as $key => $row)
        {
            $date[$key] = $row['date'];
        }

        if ($order == 'new') {
            array_multisort($date, SORT_DESC, $data);
        }

        if ($order == 'old') {
            array_multisort($date, SORT_ASC, $data);
        }

        $next = false;

        if ($page > 0) {
            $resultData = [];
            $paginate   = 10;
            $start      = $paginate * ($page - 1);
            $all        = $paginate * $page;
            $end        = $all;
            $next       = true;

            if ($all > count($data)) {
                $end = count($data);
                $next = false;
            }

            for ($i=$start; $i < $end; $i++) {
                array_push($resultData, $data[$i]);
            }

            return ['data' => $resultData, 'status' => $next];
        }
        

        return ['data' => $data, 'status' => $next];
    }

    public function transaction($post, $id) {
        $transaction = Transaction::with('outlet', 'logTopup')->orderBy('transaction_date', 'DESC');

        if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
            $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
            $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

            $transaction->whereBetween('transaction_date', [$date_start, $date_end]);
        }

        $transaction->where(function ($query) use ($post) {
            if (!is_null($post['pickup_order'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('trasaction_type', 'Pickup Order');
                });
            }

            if (!is_null($post['delivery_order'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('trasaction_type', 'Delivery');
                });
            }

            if (!is_null($post['offline_order'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('trasaction_type', 'Offline');
                });
            }
        });

        $transaction->where(function ($query) use ($post) {
            if (!is_null($post['pending'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Pending');
                });
            }

            if (!is_null($post['paid'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Paid');
                });
            }

            if (!is_null($post['completed'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Completed');
                });
            }

            if (!is_null($post['cancel'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('transaction_payment_status', 'Cancelled');
                });
            }
        });

        $transaction->where('id_user', $id);

        $transaction = $transaction->get()->toArray();

        foreach ($transaction as $key => $value) {
            $transaction[$key]['date'] = $value['transaction_date'];
            $transaction[$key]['type'] = 'trx';
            $transaction[$key]['outlet'] = $value['outlet']['outlet_name'];
        }

        return $transaction;
    }

    public function voucher($post, $id) {
        $voucher = DealsUser::with('outlet')->orderBy('claimed_at', 'DESC');

        if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
            $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
            $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

            $voucher->whereBetween('claimed_at', [$date_start, $date_end]);
        }

        $voucher->where(function ($query) use ($post) {
            if (!is_null($post['pending'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Pending');
                });
            }

            if (!is_null($post['paid'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Paid');
                });
            }

            if (!is_null($post['completed'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Completed');
                });
            }

            if (!is_null($post['cancel'])) {
                $query->orWhere(function ($amp) use ($post) {
                    $amp->where('paid_status', 'Cancelled');
                });
            }
        });

        $voucher->where('id_user', $id);

        $voucher = $voucher->get()->toArray();

        foreach ($voucher as $key => $value) {
            $voucher[$key]['date'] = $value['claimed_at'];
            $voucher[$key]['type'] = 'voucher';
            $voucher[$key]['outlet'] = $value['outlet']['outlet_name'];
        }

        return $voucher;
    }

    public function point($post) {
        $log = LogPoint::where('id_user', $post['id'])->get();
       
        $data = [];
        
        foreach ($log as $key => $value) {
            if ($value['source'] == 'Transaction') {
                $trx = Transaction::with('outlet')->where('id_transaction', $value['id_reference'])->first();

                $log[$key]['detail'] = $trx;
                $log[$key]['type']   = 'trx';
                $log[$key]['date']   = date('Y-m-d H:i:s', strtotime($trx['transaction_date']));
                $log[$key]['outlet'] = $trx['outlet']['outlet_name'];
                if ($trx['trasaction_type'] == 'Offline') {
                    $log[$key]['online'] = 0;
                } else {
                    $log[$key]['online'] = 1;
                }
            } else {
                $vou = DealsUser::with('dealVoucher.deal')->where('id_deals_user', $value['id_reference'])->first();

                $log[$key]['detail'] = $vou;
                $log[$key]['type']   = 'voucher';
                $log[$key]['date']   = date('Y-m-d H:i:s', strtotime($vou['claimed_at']));
                $log[$key]['outlet'] = $vou['outlet']['outlet_name'];
                $log[$key]['online'] = 1;
            }

            if (!is_null($post['date_start']) && !is_null($post['date_end'])) {
                $date_start = date('Y-m-d', strtotime($post['date_start']))." 00.00.00";
                $date_end = date('Y-m-d', strtotime($post['date_end']))." 23.59.59";

                if ($log[$key]['date'] < $date_start || $log[$key]['date'] > $date_end) {
                    unset($log[$key]);
                    continue;
                }
            }

            if (!is_null($post['use_point']) && !is_null($post['earn_point']) && !is_null($post['online_order']) && !is_null($post['offline_order']) && !is_null($post['voucher'])) {
            }

            if (!is_null($post['use_point']) && !is_null($post['earn_point'])) {
               
            } elseif (is_null($post['use_point']) && is_null($post['earn_point'])) {
               
            } else {
                if (!is_null($post['use_point'])) {
                    if ($value['source'] == 'Transaction') {
                        unset($log[$key]);
                        continue;
                    }
                }

                if (!is_null($post['earn_point'])) {
                    if ($value['source'] != 'Transaction') {
                        unset($log[$key]);
                        continue;
                    }
                }
            }


            if (!is_null($post['online_order']) && !is_null($post['offline_order']) && !is_null($post['voucher'])) {
                
            } elseif (is_null($post['online_order']) && is_null($post['offline_order']) && is_null($post['voucher'])) {
                
            } else {
                if (!is_null($post['online_order'])) {
                    if (is_null($post['voucher'])) {
                        if ($log[$key]['type'] == 'voucher') {
                            unset($log[$key]);
                            continue;
                        }
                    }

                    if ($log[$key]['online'] == 0) {
                        unset($log[$key]);
                        continue;
                    }
                }

                if (!is_null($post['offline_order'])) {
                    if ($log[$key]['online'] != 0) {
                        unset($log[$key]);
                        continue;
                    }
                }

                if (!is_null($post['voucher'])) {
                    if ($log[$key]['type'] != 'voucher') {
                        unset($log[$key]);
                        continue;
                    }
                }
            }

        }

        return $log->toArray();
    }

    function pointTest($post) {
        $log = DB::table('log_points')->paginate();


    }
}
