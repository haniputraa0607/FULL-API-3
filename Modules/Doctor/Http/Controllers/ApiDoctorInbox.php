<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use Modules\Doctor\Entities\Doctor;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\DoctorScheduleDate;
use Modules\Doctor\Entities\DoctorInbox;
use Modules\Doctor\Http\Requests\ScheduleCreateRequest;
use Modules\Doctor\Http\Requests\MarkedDoctorInbox;
use Modules\Doctor\Http\Requests\DeleteDoctorInbox;
use App\Lib\MyHelper;
use DB;

class ApiDoctorInbox extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
    }

    public function listInbox(Request $request, $mode = null)
    {
        $user = $request->user();
        $today = date("Y-m-d H:i:s");
        $arrInbox = [];
        $countUnread = 0;
        $countInbox = 0;
        $arrDate = [];
        $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'inbox_max_days')->pluck('value')->first() ?: 30) * 86400));

        $privates = DoctorInbox::where('id_doctor', $user->id_doctor)
                    ->whereDate('inboxes_send_at', '>', $max_date);

        if ($request->category_code) {
            $privates->where('inboxes_category', $request->category_code);
        }

        $privates = $privates->get()->toArray();

        foreach ($privates as $i => $private) {
            $content = [];
            $content['inbox_from_title']   = '';
            $content['id_inbox']     = $private['id_doctor_inboxes'];
            $content['subject']      = $private['inboxes_subject'];
            $content['clickto']      = $private['inboxes_clickto'];
            $content['cancelled']    = $i && !($i % 6) ? true : false;

            if ($private['inboxes_id_reference']) {
                $content['id_reference'] = $private['inboxes_id_reference'];
            } else {
                $content['id_reference'] = 0;
            }

            if (!empty($private['inboxes_id_reference']) && $private['inboxes_clickto'] == 'History Transaction') {
                $arrTransactionFrom = [
                    'outlet-service' => 'Outlet',
                    'home-service' => 'Home Service',
                    'shop' => 'Shop',
                    'academy' => 'Academy'
                ];
                $dtTrx = Transaction::leftJoin('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                                    ->leftJoin('brands', 'transaction_products.id_brand', 'brands.id_brand')
                                    ->where('transactions.id_transaction', $private['inboxes_id_reference'])
                                    ->select('transaction_from', 'brands.name_brand')->first();
                if (!empty($dtTrx)) {
                    if ($dtTrx['transaction_from'] == 'outlet-service') {
                        $content['inbox_from_title']   = $dtTrx['name_brand'];
                    } else {
                        $content['inbox_from_title']   = $arrTransactionFrom[$dtTrx['transaction_from']] ?? '';
                    }
                }
            }

            if ($content['clickto'] == 'Deals Detail') {
                $content['id_brand'] = $private['id_brand'];
            }

            if ($content['clickto'] == 'News') {
                $news = News::find($private['inboxes_id_reference']);
                if ($news) {
                    $content['news_title'] = $news->news_title;
                    $content['url'] = config('url.app_url') . 'news/webview/' . $news->id_news;
                }
            }

            $content['content'] = $private['inboxes_content'];
            $content['category'] = config('inboxcategory')['doctor'][$private['inboxes_category']]['name'] ?? $private['inboxes_category'];


            if ($content['clickto'] == 'Link') {
                $content['link'] = $private['inboxes_link'];
            } else {
                $content['link'] = null;
            }

            $content['created_at']   = $private['inboxes_send_at'];

            if ($private['read'] == '0') {
                $content['status'] = 'unread';
                $countUnread++;
            } else {
                $content['status'] = 'read';
            }
            if ($mode == 'simple') {
                $content['date_indo'] = MyHelper::dateFormatInd($content['created_at'], true, false, true);
                $content['time'] = date('H:i', strtotime($content['created_at']));
                $arrInbox[] = $content;
            } else {
                if (!in_array(date('Y-m-d', strtotime($content['created_at'])), $arrDate)) {
                    $arrDate[] = date('Y-m-d', strtotime($content['created_at']));
                    $temp['created'] =  date('Y-m-d', strtotime($content['created_at']));
                    $temp['list'][0] =  $content;
                    $arrInbox[] = $temp;
                } else {
                    $position = array_search(date('Y-m-d', strtotime($content['created_at'])), $arrDate);
                    $arrInbox[$position]['list'][] = $content;
                }
            }

            $countInbox++;
        }

        $messages = [];
        if (empty($arrInbox)) {
            $messages  = ['Belum ada pesan'];
        }

        if ($mode == 'simple') {
            usort($arrInbox, function ($a, $b) {
                $t1 = strtotime($a['created_at']);
                $t2 = strtotime($b['created_at']);
                return $t2 - $t1;
            });
        } else {
            foreach ($arrInbox as $key => $value) {
                usort($arrInbox[$key]['list'], function ($a, $b) {
                    $t1 = strtotime($a['created_at']);
                    $t2 = strtotime($b['created_at']);
                    return $t2 - $t1;
                });
            }

            usort($arrInbox, function ($a, $b) {
                $t1 = strtotime($a['created']);
                $t2 = strtotime($b['created']);
                return $t2 - $t1;
            });
        }

        $result = [
            'inbox' => $arrInbox,
            'count' => $countInbox,
            'count_unread' => $countUnread,
            'empty_text' => $messages
        ];

        return MyHelper::checkGet($result);
    }

    public function markedInbox(MarkedDoctorInbox $request)
    {
        $user = $request->user();
        $post = $request->json()->all();
        $availableInboxType = ['single','multiple','all'];
        if (!in_array($post['type'] ?? null, $availableInboxType)) {
            $result = [
                'status'  => 'fail',
                'messages'  => ['Inbox type not found']
            ];
        }

        if ($post['type'] == 'single') {
            $hsInbox = DoctorInbox::where('id_doctor_inboxes', $post['id_inbox'] ?? null)->first();
            if (!empty($hsInbox)) {
                $update = DoctorInbox::where('id_doctor_inboxes', $post['id_inbox'] ?? null)->update(['read' => '1']);
                $countUnread = $this->listInboxUnread($user['id_doctor']);
                $result = [
                    'status'  => 'success',
                    'result'  => ['count_unread' => $countUnread]
                ];
            } else {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Inbox not found']
                ];
            }
        } elseif ($post['type'] == 'multiple') {
            if (!empty($post['id_inboxes'])) {
                $update = DoctorInbox::whereIn('id_doctor_inboxes', $post['id_inboxes'])
                        ->where('id_doctor', $user['id_doctor'])
                        ->update(['read' => '1']);
            }

            $countUnread = $this->listInboxUnread($user['id_doctor']);
            $result = [
                'status'  => 'success',
                'result'  => ['count_unread' => $countUnread]
            ];
        } elseif ($post['type'] == 'all') {
            $update = DoctorInbox::where('id_doctor', $user['id_doctor'])
                ->update(['read' => '1']);

            $result = [
                'status'  => 'success',
                'result'  => ['count_unread' => 0]
            ];
        }

        return response()->json($result);
    }

    public function unmarkInbox(MarkedDoctorInbox $request)
    {
        $user = $request->user();
        $post = $request->json()->all();
        $availableInboxType = ['single','multiple'];
        if (!in_array($post['type'] ?? null, $availableInboxType)) {
            $result = [
                'status'  => 'fail',
                'messages'  => ['Inbox type not found']
            ];
        }

        if ($post['type'] == 'single') {
            $hsInbox = DoctorInbox::where('id_doctor_inboxes', $post['id_inbox'])->first();
            if (!empty($hsInbox)) {
                $update = DoctorInbox::where('id_doctor_inboxes', $post['id_inbox'])->update(['read' => '0']);
                $countUnread = $this->listInboxUnread($user['id_doctor']);
                $result = [
                    'status'  => 'success',
                    'result'  => ['count_unread' => $countUnread]
                ];
            } else {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Inbox not found']
                ];
            }
        } elseif ($post['type'] == 'multiple') {
            if (!empty($post['id_inboxes'])) {
                $update = DoctorInbox::whereIn('id_doctor_inboxes', $post['id_inboxes'])
                        ->where('id_doctor', $user['id_doctor'])
                        ->update(['read' => '0']);
            }
            $countUnread = $this->listInboxUnread($user['id_doctor']);
            $result = [
                'status'  => 'success',
                'result'  => ['count_unread' => $countUnread]
            ];
        }
        return response()->json($result);
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        $countUnread = $this->listInboxUnread($user->id_doctor);
        return [
            'status' => 'success',
            'result' => ['unread' => $countUnread]
        ];
    }

    public function listInboxUnread($id_doctor)
    {
        $user = Doctor::find($id_doctor);

        $today = date("Y-m-d H:i:s");
        $countUnread = 0;
        $setting_date = Setting::select('value')->where('key', 'inbox_max_days')->pluck('value')->first();
        $max_date = date('Y-m-d', time() - ((is_numeric($setting_date) ? $setting_date : 30) * 86400));

        $privates = DoctorInbox::where('id_doctor', '=', $user['id_doctor'])
                    ->where('read', '0')
                    ->whereDate('inboxes_send_at', '>', $max_date)
                    ->get();

        $countUnread = $countUnread + count($privates);

        return $countUnread;
    }

    public function deleteInbox(DeleteDoctorlistInbox $request)
    {
        $delete = DoctorInbox::where('id_doctor_inboxes', $request->json('id_inbox'))->delete();
        return MyHelper::checkDelete($delete);
    }
}
