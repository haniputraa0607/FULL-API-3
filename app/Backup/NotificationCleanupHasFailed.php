<?php

namespace App\Backup;

use App\Http\Models\Setting;
use Spatie\Backup\Notifications\Notifiable;
use App\Lib\SendMail as Mail;

class NotificationCleanupHasFailed extends Notifiable
{

    public function send()
    {
        $getSetting = Setting::where('key', 'LIKE', 'email%')->get()->toArray();
        $setting = array();
        foreach ($getSetting as $key => $value) {
            $setting[$value['key']] = $value['value'];
        }
        $data = array(
            'html_message' => "Hello Team, <br> Date : ".date('l').",".date('d')." ".date('F')." ".date('Y')." <br> A failure occurred in cleanup process, please check this process.",
            'setting' => $setting
        );
        $mailMessage = Mail::send('emails.test', $data, function ($message) use ($setting){
            $message->subject('Clean up File API');
            if(!empty($setting['email_from']) && !empty($setting['email_sender'])){
                $message->from($setting['email_sender'], $setting['email_from']);
            }else if(!empty($setting['email_sender'])){
                $message->from($setting['email_sender']);
            }
            $message->to(env('BACKUP_MAIL_TO'));
        });

        return $mailMessage;
    }
}