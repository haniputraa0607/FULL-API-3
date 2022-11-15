<?php

namespace App\Lib;

use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ServerErrorResponseException;
use GuzzleHttp\Client;
use Modules\Transaction\Entities\LogShipper;

class Shipper
{
    public static function sendRequest($subject, $method, $url, $body)
    {
        $jsonBody = json_encode($body);

        $header = [
            'Content-Type'  => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => config('deliveryshipper.api_key')
        ];
        $client = new Client([
            'headers' => $header
        ]);

        $urlApi = config('deliveryshipper.base_url') . $url;

        try {
            $output = $client->request($method, $urlApi, ['body' => $jsonBody]);
            $output = json_decode($output->getBody(), true);

            $dataLog = [
                'subject' => $subject,
                'request' => $jsonBody,
                'request_url' => $urlApi,
                'response' => json_encode($output)
            ];
            LogShipper::create($dataLog);
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
                    LogShipper::create($dataLog);
                    return ['status' => 'fail', 'response' => json_decode($response, true)];
                }
                $dataLog['response'] = 'Check your internet connection.';
                LogShipper::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            } catch (Exception $e) {
                $dataLog['response'] = 'Check your internet connection.';
                LogShipper::create($dataLog);
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
        }
    }

    public static function listPrice($data, $listAvailable)
    {
        $logo = [];
        $dtRate = [];

        foreach ($data['pricings'] ?? [] as $value) {
            $logisticCode = strtolower($value['logistic']['code']);
            $rateType = strtolower($value['rate']['type']);
            $dtRate[$logisticCode . '_' . $rateType] = [
                'price' => $value['final_price'],
                'rate_id' => $value['rate']['id'],
                'insurance_fee' => $value['insurance_fee'],
                'min_day' => $value['min_day'],
                'max_day' => $value['max_day'],
                'must_use_insurance' => $value['must_use_insurance']
            ];
            $logo[$logisticCode] = $value['logistic']['logo_url'];
        }

        $finalResult = [];
        foreach ($listAvailable as $delivery) {
            if (isset($logo[$delivery['delivery_method']])) {
                $service = [];
                foreach ($delivery['service'] as $s) {
                    $codeSearch = $s['code'];
                    if (isset($dtRate[$codeSearch])) {
                        $min = $dtRate[$codeSearch]['min_day'];
                        $max = $dtRate[$codeSearch]['max_day'];
                        $estimated = '';
                        if ($min == $max) {
                            $estimated = $max . ' hari';
                        } else {
                            $estimated = $min . '-' . $max . ' hari';
                        }

                        $service[] = [
                            "code" => $s['code'],
                            "service_name" => $s['service_name'],
                            "active_status" => $s['active_status'],
                            "price" => $dtRate[$codeSearch]['price'],
                            "rate_id" => $dtRate[$codeSearch]['rate_id'],
                            "insurance_fee" => $dtRate[$codeSearch]['insurance_fee'],
                            "must_use_insurance" => $dtRate[$codeSearch]['must_use_insurance'],
                            "estimated" => $estimated
                        ];
                    }
                }

                $finalResult[] = [
                    "delivery_name" => $delivery['delivery_name'],
                    "delivery_method" => $delivery['delivery_method'],
                    "logo" => empty($logo[$delivery['delivery_method']]) ? $delivery['logo'] : $logo[$delivery['delivery_method']],
                    "service" => $service
                ];
            }
        }

        return $finalResult;
    }
}
