<?php

namespace Modules\Xendit\Lib;

use Xendit\HttpClientInterface;
use GuzzleHttp\Client as Guzzle;
use Modules\Xendit\Entities\LogXendit;
use GuzzleHttp\Psr7;

/**
 * Xendit custom http client for logging purpose
 */
class CustomHttpClient implements HttpClientInterface
{
    public static $request_type = 'unknown';
    public static $id_reference = null;

    private $_guzz;

    public static function setLogType(string $type)
    {
        static::$request_type = $type;
    }

    public static function setIdReference($id_reference)
    {
        static::$id_reference = $id_reference;
    }

    public function __construct(Guzzle $guzz)
    {
        $this->_guzz = $guzz;
    }

    public function request($method, $uri, array $options = [])
    {
        $response = $this->_guzz->request($method, $uri, $options);

        LogXendit::create([
            'type'                 => static::$request_type,
            'id_reference'         => static::$id_reference,
            'request'              => ($options['json'] ?? null) ? json_encode($options['json']) : null,
            'request_url'          => $this->buildUri($uri, $this->_guzz->getConfig()),
            'request_method'       => $method,
            'request_header'       => ($options['headers'] ?? null) ? json_encode($options['headers']) : null,
            'response'             => $response->getBody()->__toString(),
            'response_status_code' => $response->getStatusCode(),
        ]);
        return $response;
    }

    private function buildUri($uri, array $config)
    {
        // for BC we accept null which would otherwise fail in uri_for
        $uri = Psr7\uri_for($uri === null ? '' : $uri);

        if (isset($config['base_uri'])) {
            $uri = Psr7\UriResolver::resolve(Psr7\uri_for($config['base_uri']), $uri);
        }

        return $uri->getScheme() === '' && $uri->getHost() !== '' ? $uri->withScheme('http') : $uri;
    }
}
