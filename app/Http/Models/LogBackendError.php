<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Debug\Exception\FlattenException;

class LogBackendError extends Model
{
	protected $connection = 'mysql2';
	protected $table = 'log_backend_errors';
	protected $primaryKey = 'id_log_backend_error';
	protected $fillable = [
		'response_status',
		'url',
		'request_method',
		'error',
		'file',
		'line',
		'ip_address',
		'user_agent',
		'created_at',
		'updated_at'
	];

	static function logExceptionMessage($errorMessage, $e)
	{
		// $serviceAccount = ServiceAccount::fromJsonFile(base_path() . '/resources/assets/kk-logs-firebase-adminsdk-0giqw-667268ea48.json');
		// $firebase = (new Factory)
		// 	->withServiceAccount($serviceAccount)
		// 	->withDatabaseUri('https://kk-logs.firebaseio.com/')
		// 	->create();
		// $database = $firebase->getDatabase();
		//
		$e = FlattenException::create($e);
		if ($e->getStatusCode()) {
			$ip = $_SERVER['REMOTE_ADDR'];
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			$request_method = $_SERVER['REQUEST_METHOD'];
			if ($ip == "::1") $ip = "Localhost";
			$errors = array();
			$errors['error'] = $e->getMessage() ? $e->getMessage() : 'Not Found';
			$errors['url'] = \Request::fullUrl();
			$errors['file'] = $e->getFile();
			$errors['line'] = $e->getLine();
			$errors['ip_address'] = $ip;
			$errors['user_agent'] = $user_agent;
			$errors['request_method'] = $request_method;
			$botUserAgents = ['AWS Security Scanner', 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)', 'Go-http-client/1.1'];
			$unRecordErrors = ['Unauthenticated.'];
			if (!in_array($errors['user_agent'], $botUserAgents) && !in_array($errors['error'], $unRecordErrors)) {
				//send notification to firebase
				// $newErrorLog = $database
				// 	->getReference()
				// 	->push([
				// 		'errors' => $errors,
				// 	]);
				if (env('SLACK_NOTIF', 'OFF') == "ON") {
					$errorTxt = '';
					foreach ($errors as $key => $value) {
						$errorTxt .= '*' . $key . '* : ' . $value . PHP_EOL;
					}
					// send notif to slack
					\Slack::to(env('SLACK_CHANNEL'))->send($errorTxt);
				}
				if (env('ERROR_SAVE', 'OFF') == "ON") {
					//record to log table
					LogBackendError::create($errors);
				}
			}
		}
	}
}
