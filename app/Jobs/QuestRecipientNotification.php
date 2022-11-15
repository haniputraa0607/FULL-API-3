<?php

namespace App\Jobs;

use App\Http\Models\Autocrm;
use App\Http\Models\AutocrmEmailLog;
use App\Http\Models\Setting;
use App\Lib\SendMail as Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Quest\Entities\Quest;
use Modules\Quest\Entities\QuestUser;
use Modules\Users\Http\Controllers\ApiUser;
use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;

class QuestRecipientNotification implements ShouldQueue
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
        $id_quest = $this->data['id_quest'];
        $dataQuest = Quest::where('id_quest', $id_quest)->first();
        $crm = Autocrm::where('autocrm_title', '=', 'Quest Voucher Ended')->first();

        if (!empty($crm)) {
            $crm['status_forwad'] = 0;
            $variables = [
                'quest_name' => $dataQuest['name'],
                'deals_title' => $this->data['deals']['deals_title']
            ];
            $subject = $this->textReplace($crm['autocrm_forward_email_subject'], $variables);
            $content = $this->textReplace($crm['autocrm_forward_email_content'], $variables);

            $users = QuestUser::leftJoin('quest_user_redemptions', function ($join) {
                    $join->on('quest_user_redemptions.id_quest', 'quest_users.id_quest')
                        ->whereColumn('quest_user_redemptions.id_user', 'quest_users.id_user');
            })
                ->join('users', 'users.id', 'quest_users.id_user')
                ->where('date_end', '>', date('Y-m-d'))
                ->where('quest_users.id_quest', $id_quest)
                ->where(function ($query) {
                    $query->whereNull('id_quest_user_redemption')
                        ->orWhere('redemption_status', 0);
                })->groupBy('users.phone')->select('users.phone')
                ->chunk(600, function ($users) use ($id_quest, $subject, $content, $crm) {
                    $recipient = [];
                    foreach ($users as $val) {
                        $recipient[] = $val->phone;
                    }
                    $data = [
                        'variables' => ['id_quest' => $id_quest],
                        'recipient' => $recipient,
                        'subject' => $subject,
                        'content' => $content,
                        'autoresponse' => $crm
                    ];
                    QuestSendNotification::dispatch($data)->allOnConnection('quest');
                    $crm['status_forwad'] = 1;
                });

            if ($crm['status_forwad'] == 1 && $crm['autocrm_forward_toogle'] == 1) {
                $this->sendForwardNotification([
                    'autoresponse' => $crm,
                    'subject' => $subject,
                    'content' => $content
                ]);
            }
        }

        return true;
    }

    public function sendForwardNotification($data)
    {
        $crm = $data['autoresponse'];
        $autocrmTitle = $crm['autocrm_title'];
        $exparr = explode(';', str_replace(',', ';', $crm['autocrm_forward_email']));
        foreach ($exparr as $email) {
            $n   = explode('@', $email);
            $name = $n[0];

            $to      = $email;

            // get setting email
            $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
            $setting = array();
            foreach ($getSetting as $key => $value) {
                if ($value['key'] == 'email_setting_url') {
                    $setting[$value['key']]  = (array)json_decode($value['value_text']);
                } else {
                    $setting[$value['key']] = $value['value'];
                }
            }

            $subject = $data['subject'];
            $content = $data['content'];

            $data = array(
                'customer' => $name,
                'html_message' => $content,
                'setting' => $setting
            );
            try {
                Mail::send('emails.test', $data, function ($message) use ($to, $subject, $name, $setting, $autocrmTitle, $crm) {
                    $message->to($to, $name)->subject($subject);
                    if (!empty($setting['email_from']) && !empty($setting['email_sender'])) {
                        $message->from($setting['email_sender'], $setting['email_from']);
                    } elseif (!empty($setting['email_sender'])) {
                        $message->from($setting['email_sender']);
                    }

                    if (!empty($setting['email_reply_to']) && !empty($setting['email_reply_to_name'])) {
                        $message->replyTo($setting['email_reply_to'], $setting['email_reply_to_name']);
                    } elseif (!empty($setting['email_reply_to'])) {
                        $message->replyTo($setting['email_reply_to']);
                    }

                    if (!empty($setting['email_cc']) && !empty($setting['email_cc_name'])) {
                        $message->cc($setting['email_cc'], $setting['email_cc_name']);
                    }

                    if (!empty($setting['email_bcc']) && !empty($setting['email_bcc_name'])) {
                        $message->bcc($setting['email_bcc'], $setting['email_bcc_name']);
                    }
                });
            } catch (\Exception $e) {
                \Log::error($e);
            }
        }
    }

    public function textReplace($text, $variables)
    {
        if (!empty($variables)) {
            foreach ($variables as $key => $var) {
                if (is_string($var)) {
                    $text = str_replace('%' . $key . '%', $var, $text);
                }
            }
        }

        return $text;
    }
}
