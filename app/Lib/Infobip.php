<?php

namespace App\Lib;

use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ServerErrorResponseException;
use GuzzleHttp\Client;
use Modules\Consultation\Entities\LogInfobip;

class Infobip
{
    public static function sendRequest($subject, $method, $url, $body)
    {
        $jsonBody = json_encode($body);

        $header = [
            'Content-Type'  => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => config('infobip.api_key')
        ];
        $client = new Client([
            'headers' => $header
        ]);

        $urlApi = config('infobip.base_url') . $url;

        try {
            $output = $client->request($method, $urlApi, ['body' => $jsonBody]);
            $output = json_decode($output->getBody(), true);

            $dataLog = [
                'subject' => $subject,
                'request' => $jsonBody,
                'request_url' => $urlApi,
                'response' => json_encode($output)
            ];
            LogInfobip::create($dataLog);
            return ['status' => 'success', 'response' => $output];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $dataLog = [
                'subject' => $subject,
                'request' => $jsonBody,
                'request_url' => $urlApi
            ];

            try {
                if ($e->getResponse()) {
                    $response = $e->getResponse()->getBody()->getContents();
                    $dataLog['response'] = $response;
                    LogInfobip::create($dataLog);
                    return ['status' => 'fail', 'response' => json_decode($response, true)];
                }
                $dataLog['response'] = 'Check your internet connection.';
                LogInfobip::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            } catch (Exception $e) {
                $dataLog['response'] = 'Check your internet connection.';
                LogInfobip::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
        }
    }

    public static function getRequest($subject, $method, $url)
    {
        $header = [
            'Accept' => 'application/json',
            'Authorization' => config('infobip.api_key')
        ];
        $client = new Client([
            'headers' => $header
        ]);

        $urlApi = config('infobip.base_url') . $url;

        try {
            $output = $client->request($method, $urlApi);
            $output = json_decode($output->getBody(), true);

            $dataLog = [
                'subject' => $subject,
                'request_url' => $urlApi,
                'response' => json_encode($output)
            ];
            LogInfobip::create($dataLog);
            return ['status' => 'success', 'response' => $output];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $dataLog = [
                'subject' => $subject,
                'request_url' => $urlApi
            ];

            try {
                if ($e->getResponse()) {
                    $response = $e->getResponse()->getBody()->getContents();
                    $dataLog['response'] = $response;
                    LogInfobip::create($dataLog);
                    return ['status' => 'fail', 'response' => json_decode($response, true)];
                }
                $dataLog['response'] = 'Check your internet connection.';
                LogInfobip::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            } catch (Exception $e) {
                $dataLog['response'] = 'Check your internet connection.';
                LogInfobip::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
        }
    }

    public static function generateInfobipToken($tokenable)
    {
        $url = '/webrtc/1/token';
        $body = [
            'identity' => $tokenable->infobip_identity,
            'applicationId' => config('infobip.rtc_application_id'),
            'displayName' => $tokenable->name,
            'capabilities' => [
                'recording' => 'ALWAYS'
            ],
            'timeToLive' => 8 * 3600,
        ];

        $response = static::sendRequest('Generate RTC Token', 'POST', $url, $body);
        if (($response['status'] ?? '') == 'success') {
            $token = $response['response']['token'] ?? false;
            $tokenable->infobipTokens()->create([
                'token' => $token,
                'expired_at' => date('Y-m-d H:i:s', time() + 3 * 3600),
            ]);
            return $token;
        }

        return false;
    }
}
