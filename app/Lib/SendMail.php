<?php

namespace App\Lib;

use App\Http\Models\Setting;
use Mail;
use App\Mail\GenericMail;

class SendMail
{

    public static function send($view, $data = [], $callback = null)
    {
		$mail = (new GenericMail())->view($view, $data);
		if($callback) {
			$callback($mail);
			$to = $mail->to[0]??false;
			if ($to) {
				$mail->to = [['address' => trim($to['address']), 'name' => trim($to['name'])]];
			}
		}

        $setting_raw = Setting::where('key', 'like', 'mailer_%')->get();
        $settings    = [];
        foreach ($setting_raw as $setting) {
            $settings[$setting['key']] = $setting['value'];
        }

        //for yahoo, hotmail, rocketmail use env 2
        if (strpos($to['address'], 'yahoo') !== false || strpos($to['address'], 'hotmail') !== false || strpos($to['address'], 'rocketmail') !== false) {
            $envMail = 2;
        }
        //for other using env default
            $envMail = '';
        }

        $config               = config('mail');
        // $config['host']       = $settings['mailer_smtp_host'] ?? $config['host'];
        // $config['port']       = $settings['mailer_smtp_port'] ?? $config['port'];
        // $config['encryption'] = $settings['mailer_smtp_encryption'] ?? $config['encryption'];
        // $config['username']   = $settings['mailer_smtp_username'] ?? $config['username'];
        // $config['password']   = $settings['mailer_smtp_password'] ?? $config['password'];
        $config['host']       = env('MAIL_HOST'.$envMail, 'smtp.mailgun.org');
        $config['port']       = env('MAIL_PORT'.$envMail, 587);
        $config['encryption'] = env('MAIL_ENCRYPTION'.$envMail, 'tls');
        $config['username']   = env('MAIL_USERNAME'.$envMail);
        $config['password']   = env('MAIL_PASSWORD'.$envMail);

        $transport = app('swift.transport');
        $smtp      = $transport->driver('smtp');
        $smtp->setHost($config['host']);
        $smtp->setPort($config['port']);
        $smtp->setUsername($config['username']);
        $smtp->setPassword($config['password']);
        $smtp->setEncryption($config['encryption']);

        Mail::send($mail);
    }
}
