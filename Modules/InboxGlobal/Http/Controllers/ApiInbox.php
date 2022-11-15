<?php

namespace Modules\InboxGlobal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\User;
use App\Http\Models\UserInbox;
use App\Http\Models\InboxGlobal;
use App\Http\Models\InboxGlobalRule;
use App\Http\Models\InboxGlobalRuleParent;
use App\Http\Models\InboxGlobalRead;
use App\Http\Models\News;
use App\Http\Models\Setting;
use Modules\InboxGlobal\Http\Requests\MarkedInbox;
use Modules\InboxGlobal\Http\Requests\DeleteUserInbox;
use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;

class ApiInbox extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->user     = "Modules\Users\Http\Controllers\ApiUser";
        $this->inboxGlobal  = "Modules\InboxGlobal\Http\Controllers\ApiInboxGlobal";
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function deleteInboxUser(DeleteUserInbox $request)
    {
        $delete = UserInbox::where('id_user_inboxes', $request->json('id_inbox'))->delete();
        return MyHelper::checkDelete($delete);
    }

    public function listInboxUser(Request $request, $mode = false)
    {
        if (is_numeric($phone = $request->json('phone'))) {
            $user = User::where('phone', $phone)->first();
        } else {
            $user = $request->user();
        }

        $today = date("Y-m-d H:i:s");
        $arrInbox = [];
        $countUnread = 0;
        $countInbox = 0;
        $arrDate = [];
        $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'inbox_max_days')->pluck('value')->first() ?: 30) * 86400));
        $globals = InboxGlobal::with('inbox_global_rule_parents', 'inbox_global_rule_parents.rules')
                                ->where('inbox_global_start', '<=', $today)
                                ->where('inbox_global_end', '>=', $today)
                                ->whereDate('inbox_global_start', '>', $max_date)
                                ->get()
                                ->toArray();

        foreach ($globals as $ind => $global) {
            $cons = array();
            $cons['subject'] = 'phone';
            $cons['operator'] = '=';
            $cons['parameter'] = $user['phone'];

            array_push($global['inbox_global_rule_parents'], ['rule' => 'and', 'rule_next' => 'and', 'rules' => [$cons]]);
            $users = app($this->user)->UserFilter($global['inbox_global_rule_parents']);


            if (isset($users['status']) && $users['status'] == 'success') {
                $content = [];
                $content['type']         = 'global';
                $content['id_inbox']     = $global['id_inbox_global'];
                $content['subject']      = app($this->autocrm)->TextReplace($global['inbox_global_subject'], $user['phone']);
                $content['content']      = app($this->autocrm)->TextReplace($global['inbox_global_content'], $user['phone']);
                $content['clickto']      = $global['inbox_global_clickto'];


                if ($global['inbox_global_id_reference']) {
                    $content['id_reference'] = $global['inbox_global_id_reference'];
                } else {
                    $content['id_reference'] = 0;
                }

                if ($content['clickto'] == 'News') {
                    $news = News::find($global['inbox_global_id_reference']);
                    if ($news) {
                        $content['news_title'] = $news->news_title;
                        $content['url'] = config('url.app_url') . 'news/webview/' . $news->id_news;
                    }
                }

                if ($content['clickto'] == 'Link') {
                    $content['link'] = $global['inbox_global_link'];
                } else {
                    $content['link'] = null;
                }

                $content['created_at']   = $global['inbox_global_start'];

                $read = InboxGlobalRead::where('id_inbox_global', $global['id_inbox_global'])->where('id_user', $user['id'])->first();
                if (!empty($read)) {
                    $content['status'] = 'read';
                } else {
                    $content['status'] = 'unread';
                    $countUnread++;
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
        }

        $privates = UserInbox::where('id_user', '=', $user['id'])->where('inboxes_promotion_status', 0)->whereDate('inboxes_send_at', '>', $max_date)->get()->toArray();

        foreach ($privates as $private) {
            $content = [];
            $content['type']         = 'private';
            $content['id_inbox']     = $private['id_user_inboxes'];
            $content['subject']      = $private['inboxes_subject'];
            $content['content'] = $private['inboxes_content'];
            $content['clickto']      = $private['inboxes_clickto'];

            if ($private['inboxes_id_reference']) {
                $content['id_reference'] = $private['inboxes_id_reference'];
            } else {
                $content['id_reference'] = 0;
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

            if ($content['clickto'] == 'Link') {
                $content['link'] = $private['inboxes_link'];
            } else {
                $content['link'] = null;
            }

            $content['created_at']   = $private['inboxes_send_at'];

            if ($private['read'] === '0') {
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
                    $content['created_at']   = date('H:i', strtotime($content['created_at']));
                    $temp['list'][0] =  $content;
                    $arrInbox[] = $temp;
                } else {
                    $position = array_search(date('Y-m-d', strtotime($content['created_at'])), $arrDate);
                    $content['created_at']   = date('H:i', strtotime($content['created_at']));
                    $arrInbox[$position]['list'][] = $content;
                }
            }

            $countInbox++;
        }

        if (isset($arrInbox) && !empty($arrInbox)) {
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

                foreach ($arrInbox as $key => $data) {
                    $currentDate = date('d/m/Y');
                    $dateConvert = date('d/m/Y', strtotime($data['created']));
                    if ($currentDate == $dateConvert) {
                        $date =  'Hari ini';
                    } else {
                        $date = $dateConvert;
                    }
                    $arrInbox[$key]['created'] = $date;
                }
            }

            $result = [
                    'status'  => 'success',
                    'result'  => $arrInbox,
                    'count'  => $countInbox,
                    'count_unread' => $countUnread,
                ];
        } else {
            $result = [
                'status'  => 'success',
                'result'  => [],
                'count'  => 0,
                'count_unread' => 0,
            ];
        }
        return response()->json($result);
    }

    public function listInboxUserPromotion(Request $request)
    {
        if (is_numeric($phone = $request->json('phone'))) {
            $user = User::where('phone', $phone)->first();
        } else {
            $user = $request->user();
        }

        $arrInbox = [];
        $countUnread = 0;
        $countInbox = 0;
        $arrDate = [];
        $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'inbox_max_days')->pluck('value')->first() ?: 30) * 86400));
        $privates = UserInbox::where('id_user', '=', $user['id'])->where('inboxes_promotion_status', 1)->whereDate('inboxes_send_at', '>', $max_date)->get()->toArray();

        foreach ($privates as $private) {
            $content = [];
            $content['type']         = 'private';
            $content['id_inbox']     = $private['id_user_inboxes'];
            $content['subject']      = $private['inboxes_subject'];
            $content['content'] = $private['inboxes_content'];
            $content['clickto']      = $private['inboxes_clickto'];

            if ($private['inboxes_id_reference']) {
                $content['id_reference'] = $private['inboxes_id_reference'];
            } else {
                $content['id_reference'] = 0;
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

            if ($content['clickto'] == 'Link') {
                $content['link'] = $private['inboxes_link'];
            } else {
                $content['link'] = null;
            }

            $content['created_at']   = $private['inboxes_send_at'];

            if ($private['read'] === '0') {
                $content['status'] = 'unread';
                $countUnread++;
            } else {
                $content['status'] = 'read';
            }
            if (!in_array(date('Y-m-d', strtotime($content['created_at'])), $arrDate)) {
                $arrDate[] = date('Y-m-d', strtotime($content['created_at']));
                $temp['created'] =  date('Y-m-d', strtotime($content['created_at']));
                $content['created_at']   = date('H:i', strtotime($content['created_at']));
                $temp['list'][0] =  $content;
                $arrInbox[] = $temp;
            } else {
                $position = array_search(date('Y-m-d', strtotime($content['created_at'])), $arrDate);
                $content['created_at']   = date('H:i', strtotime($content['created_at']));
                $arrInbox[$position]['list'][] = $content;
            }

            $countInbox++;
        }

        if (isset($arrInbox) && !empty($arrInbox)) {
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

            foreach ($arrInbox as $key => $data) {
                $currentDate = date('d/m/Y');
                $dateConvert = date('d/m/Y', strtotime($data['created']));
                if ($currentDate == $dateConvert) {
                    $date =  'Hari ini';
                } else {
                    $date = $dateConvert;
                }
                $arrInbox[$key]['created'] = $date;
            }

            $result = [
                'status'  => 'success',
                'result'  => $arrInbox,
                'count'  => $countInbox,
                'count_unread' => $countUnread,
            ];
        } else {
            $result = [
                'status'  => 'success',
                'result'  => [],
                'count'  => 0,
                'count_unread' => 0,
            ];
        }
        return response()->json($result);
    }

    public function markedAllInbox(Request $request)
    {
        $user = $request->user();
        $post = $request->json()->all();

        if (empty($post['notif_type'])) {
            return response()->json(['status' => 'fail', 'messages' => ['Type can not be empty']]);
        }

        if ($post['notif_type'] == 'notification') {
            $today = date("Y-m-d H:i:s");
            $max_date = date('Y-m-d', time() - ((Setting::select('value')->where('key', 'inbox_max_days')->pluck('value')->first() ?: 30) * 86400));
            $globals = InboxGlobal::where('inbox_global_start', '<=', $today)
                ->where('inbox_global_end', '>=', $today)
                ->whereDate('inbox_global_start', '>', $max_date)
                ->pluck('id_inbox_global')->toArray();

            foreach ($globals as $id_global) {
                InboxGlobalRead::updateOrCreate(['id_inbox_global' => $id_global, 'id_user' => $user['id']], ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }

        $update = UserInbox::where('id_user', $user->id)->update(['read' => 1]);
        return response()->json(MyHelper::checkUpdate($update));
    }

    public function markedInbox(MarkedInbox $request)
    {
        $user = $request->user();
        $post = $request->json()->all();
        if ($post['type'] == 'private') {
            $userInbox = UserInbox::where('id_user_inboxes', $post['id_inbox'])->first();
            if (!empty($userInbox)) {
                $update = UserInbox::where('id_user_inboxes', $post['id_inbox'])->update(['read' => '1']);
                // if(!$update){
                //  $result = [
                //      'status'  => 'fail',
                //      'messages'  => ['Failed marked inbox']
                //  ];
                // }else{
                    $result = [
                        'status'  => 'success'
                    ];
                // }
            } else {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Inbox not found']
                ];
            }
        } elseif ($post['type'] == 'global') {
            $inboxGlobal = InboxGlobal::where('id_inbox_global', $post['id_inbox'])->first();
            if (!empty($inboxGlobal)) {
                $inboxGlobalRead = InboxGlobalRead::where('id_inbox_global', $post['id_inbox'])->where('id_user', $user['id'])->first();
                if (empty($inboxGlobalRead)) {
                    $create = InboxGlobalRead::create(['id_inbox_global' => $post['id_inbox'], 'id_user' => $user['id']]);
                    if (!$create) {
                        $result = [
                            'status'  => 'fail',
                            'messages'  => ['Failed marked inbox']
                        ];
                    }
                }

                $result = [
                    'status'  => 'success'
                ];
            } else {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Inbox not found']
                ];
            }
        } elseif ($post['type'] == 'multiple') {
            if ($post['inboxes']['global'] ?? false) {
                foreach ($post['inboxes']['global'] as $id_inbox) {
                    $inboxGlobal = InboxGlobal::where('id_inbox_global', $id_inbox)->first();
                    if ($inboxGlobal) {
                        $inboxGlobalRead = InboxGlobalRead::where('id_inbox_global', $id_inbox)->where('id_user', $user['id'])->first();
                        if (empty($inboxGlobalRead)) {
                            $create = InboxGlobalRead::create(['id_inbox_global' => $id_inbox, 'id_user' => $user['id']]);
                        }
                    }
                }
            }
            if ($post['inboxes']['private']) {
                $update = UserInbox::whereIn('id_user_inboxes', $post['inboxes']['private'])->where('id_user', $user['id'])->update(['read' => '1']);
            }
            $countUnread = $this->listInboxUnread($user['id']);
            $result = [
                'status'  => 'success',
                'result'  => ['count_unread' => $countUnread]
            ];
        }
        return response()->json($result);
    }
    /**
     * update status requested inbox to unread
     * @param  MarkedInbox $request [description]
     * @return Response               [description]
     */
    public function unmarkInbox(MarkedInbox $request)
    {
        $user = $request->user();
        $post = $request->json()->all();
        if ($post['type'] == 'private') {
            $userInbox = UserInbox::where('id_user_inboxes', $post['id_inbox'])->first();
            if (!empty($userInbox)) {
                $update = UserInbox::where('id_user_inboxes', $post['id_inbox'])->update(['read' => '0']);
                // if(!$update){
                //  $result = [
                //      'status'  => 'fail',
                //      'messages'  => ['Failed marked inbox']
                //  ];
                // }else{
                    $countUnread = $this->listInboxUnread($user['id']);
                    $result = [
                        'status'  => 'success',
                        'result'  => ['count_unread' => $countUnread]
                    ];
                // }
            } else {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Inbox not found']
                ];
            }
        } elseif ($post['type'] == 'global') {
            $inboxGlobal = InboxGlobal::where('id_inbox_global', $post['id_inbox'])->first();
            if (!empty($inboxGlobal)) {
                $delete = InboxGlobalRead::where('id_inbox_global', $post['id_inbox'])->where('id_user', $user['id'])->delete();
                if ($delete) {
                    $countUnread = $this->listInboxUnread($user['id']);
                    $result = [
                        'status'  => 'success',
                        'result'  => ['count_unread' => $countUnread]
                    ];
                } else {
                    $result = [
                        'status'  => 'fail',
                        'messages'  => ['Failed unread inbox']
                    ];
                }
            } else {
                $result = [
                    'status'  => 'fail',
                    'messages'  => ['Inbox not found']
                ];
            }
        } elseif ($post['type'] == 'multiple') {
            if ($post['inboxes']['global'] ?? false) {
                $delete = InboxGlobalRead::where('id_user', $user['id'])->whereIn('id_inbox_global', $post['inboxes']['global']);
            }
            if ($post['inboxes']['private']) {
                $update = UserInbox::whereIn('id_user_inboxes', $post['inboxes']['private'])->where('id_user', $user['id'])->update(['read' => '0']);
            }
            $countUnread = $this->listInboxUnread($user['id']);
            $result = [
                'status'  => 'success',
                'result'  => ['count_unread' => $countUnread]
            ];
        }
        return response()->json($result);
    }

    public function listInboxUnread($id_user)
    {
        $user = User::find($id_user);

        $today = date("Y-m-d H:i:s");
        $countUnread = 0;
        $setting_date = Setting::select('value')->where('key', 'inbox_max_days')->pluck('value')->first();
        $max_date = date('Y-m-d', time() - ((is_numeric($setting_date) ? $setting_date : 30) * 86400));
        $read = array_pluck(InboxGlobalRead::where('id_user', $user['id'])->get(), 'id_inbox_global');

        $globals = InboxGlobal::with('inbox_global_rule_parents', 'inbox_global_rule_parents.rules')
                            ->where('inbox_global_start', '<=', $today)
                            ->where('inbox_global_end', '>=', $today)
                            ->whereDate('inbox_global_start', '>', $max_date)
                            ->get()
                            ->toArray();

        foreach ($globals as $global) {
            $cons = array();
            $cons['subject'] = 'phone';
            $cons['operator'] = '=';
            $cons['parameter'] = $user['phone'];

            array_push($global['inbox_global_rule_parents'], ['rule' => 'and', 'rule_next' => 'and', 'rules' => [$cons]]);
            $users = app($this->user)->UserFilter($global['inbox_global_rule_parents']);

            if (($users['status'] ?? false) == 'success') {
                $read = InboxGlobalRead::where('id_inbox_global', $global['id_inbox_global'])->where('id_user', $id_user)->first();
                if (empty($read)) {
                    $countUnread += 1;
                }
            }
        }

        $privates = UserInbox::where('id_user', '=', $user['id'])->where('read', '0')->whereDate('inboxes_send_at', '>', $max_date)->get();


        $countUnread = $countUnread + count($privates);

        return $countUnread;
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        $countUnread = $this->listInboxUnread($user->id);
        return [
            'status' => 'success',
            'result' => ['unread' => $countUnread]
        ];
    }
}
