<?php

namespace App\Jobs;

use App\Http\Models\AutocrmPushLog;
use App\Http\Models\CampaignPushSent;
use App\Http\Models\User;
use App\Http\Models\UserInbox;
use App\Lib\PushNotificationHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Users\Http\Controllers\ApiUser;
use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;

class QuestSendNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $data;
    protected $camp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subject = $this->data['subject'];
        $content = $this->data['content'];
        $crm = $this->data['autoresponse'];
        $variables = $this->data['variables'];
        $recipient = $this->data['recipient'];

        if ($crm['autocrm_push_toogle'] == 1) {
            $dataOptional          = $variables['data_optional'] ?? [];
            $image = null;
            if (isset($crm['autocrm_push_image']) && $crm['autocrm_push_image'] != null) {
                $dataOptional['image'] = config('url.storage_url_api') . $crm['autocrm_push_image'];
                $image = config('url.storage_url_api') . $crm['autocrm_push_image'];
            }

            //======set id reference and type
            $dataOptional['type'] = $crm['autocrm_push_clickto'];
            if ($crm['autocrm_push_clickto'] == "No Action") {
                $dataOptional['type'] = 'Default';
                $dataOptional['id_reference'] = 0;
            } elseif ($crm['autocrm_push_clickto'] == 'Voucher') {
                if (isset($variables['id_deals_user'])) {
                    $dataOptional['id_reference'] = $variables['id_deals_user'];
                } else {
                    $dataOptional['id_reference'] = 0;
                }
            } elseif ($crm['autocrm_push_clickto'] == 'Voucher Quest') {
                if (isset($variables['id_deals_user'])) {
                    $dataOptional['id_reference'] = $variables['id_deals_user'];
                } else {
                    $dataOptional['id_reference'] = 0;
                }
            } elseif ($crm['autocrm_push_clickto'] == 'Quest') {
                if (isset($variables['id_quest'])) {
                    $dataOptional['id_reference'] = $variables['id_quest'];
                } else {
                    $dataOptional['id_reference'] = 0;
                }
            } elseif ($crm['autocrm_push_clickto'] == 'Home') {
                $dataOptional['id_reference'] = 0;
            } else {
                $dataOptional['type'] = 'Home';
                $dataOptional['id_reference'] = 0;
            }

            $deviceToken = PushNotificationHelper::searchDeviceToken("phone", $recipient);
            if (!empty($deviceToken)) {
                if (isset($deviceToken['token']) && !empty($deviceToken['token'])) {
                    try {
                        $push = PushNotificationHelper::sendPush($deviceToken['token'], $subject, $content, $image, $dataOptional, 1);

                        if (isset($push['success']) && $push['success'] > 0) {
                            $calculationSuccess = [
                                'error_token' => $push['error_token'] ?? [],
                                'all_token' => $deviceToken['token'],
                                'recipient' => $recipient,
                                'subject' => $subject,
                                'content' => $content
                            ];

                            $this->insertToLog($calculationSuccess);
                        }
                    } catch (\Exception $e) {
                        \Log::error($e);
                    }
                }
            }
        }

        if ($crm['autocrm_inbox_toogle'] == 1) {
            $user = User::whereIn('phone', $recipient)->get()->toArray();
            $inbox = [];
            foreach ($user as $key => $receipient) {
                $inboxInsert['id_user']           = $receipient['id'];
                $inboxInsert['inboxes_subject'] = $subject;
                $inboxInsert['inboxes_clickto'] = $crm['autocrm_inbox_clickto'];

                if ($crm['autocrm_inbox_clickto'] == 'Content') {
                    $inboxInsert['inboxes_content'] = $content;
                }

                if ($crm['autocrm_inbox_clickto'] == 'Link') {
                    $inboxInsert['inboxes_link'] = $crm['autocrm_inbox_link'];
                }

                //===== set id reference and click to

                if ($crm['autocrm_inbox_clickto'] == 'Voucher') {
                    if (isset($variables['id_deals_user'])) {
                        $inboxInsert['inboxes_id_reference'] = $variables['id_deals_user'];
                    } else {
                        $inboxInsert['inboxes_id_reference'] = 0;
                    }
                } elseif ($crm['autocrm_inbox_clickto'] == 'Quest') {
                    if (isset($variables['id_quest'])) {
                        $inboxInsert['inboxes_id_reference'] = $variables['id_quest'];
                    } else {
                        $inboxInsert['inboxes_id_reference'] = 0;
                    }
                } elseif ($crm['autocrm_inbox_clickto'] == 'Home') {
                    $inboxInsert['inboxes_id_reference'] = 0;
                } else {
                    $inboxInsert['inboxes_clickto'] = 'Default';
                    $inboxInsert['inboxes_id_reference'] = 0;
                }

                if (isset($crm['autocrm_inbox_id_reference']) && $crm['autocrm_inbox_id_reference'] != null) {
                    $inboxInsert['inboxes_id_reference'] = (int)$crm['autocrm_inbox_id_reference'];
                }

                $inboxInsert['inboxes_send_at'] = date("Y-m-d H:i:s");
                $inboxInsert['created_at'] = date("Y-m-d H:i:s");
                $inboxInsert['updated_at'] = date("Y-m-d H:i:s");
                $inbox[] = $inboxInsert;
            }
            $inboxQuery = UserInbox::insert($inbox);
        }

        return true;
    }

    public function insertToLog($calculationSuccess)
    {
        $all_token = $calculationSuccess['all_token'];
        $phones = $calculationSuccess['recipient'];
        foreach ($calculationSuccess['error_token'] as $error_token) {
            $check = array_search($error_token, $all_token);
            if ($check !== false) {
                unset($all_token[$check]);
            }
        }

        $indexing = array_values($all_token);
        $getUserSuccess = User::join('user_devices', 'users.id', 'user_devices.id_user')
            ->whereIn('device_token', $indexing)
            ->whereIn('phone', $phones)
            ->groupBy('phone')->select('users.phone', 'users.id')->get()->toArray();

        $logData = [];
        foreach ($getUserSuccess as $val) {
            $logData[] = [
                'id_user' => $val['id'],
                'push_log_to' => $val['phone'],
                'push_log_subject' => $calculationSuccess['subject'],
                'push_log_content' => $calculationSuccess['content'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        if ($logData) {
            AutocrmPushLog::insert($logData);
        }

        return true;
    }
}
