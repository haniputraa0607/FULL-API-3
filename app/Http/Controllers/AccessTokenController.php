<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\ClientException;
use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;
use Auth;
use App\Http\Models\User;

class AccessTokenController extends PassportAccessTokenController
{
    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public function issueToken(ServerRequestInterface $request)
    {
        //return response()->json($request->getParsedBody());
        try {
            if(isset($request->getParsedBody()['username']) && isset($request->getParsedBody()['password'])){

                if(Auth::attempt(['phone' => $request->getParsedBody()['username'], 'password' => $request->getParsedBody()['password']])){
                    $user = User::where('phone', $request->getParsedBody()['username'])->first();
                    if($user){
                        //check if user already suspended
                        if($user->is_suspended == '1'){
                            return response()->json(['status' => 'fail', 'messages' => 'Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.env('EMAIL_ADDRESS_ADMIN')]);
                        }

                        //check if otp have expired and the current time exceeds the expiration time
                        if(!is_null($user->otp_valid_time) && strtotime(date('Y-m-d H:i:s')) > strtotime($user->otp_valid_time)){
                            return response()->json(['status' => 'fail', 'messages' => 'This OTP is expired, please re-request OTP from apps']);
                        }

                        if(isset($request->getParsedBody()['scope'])){
                            if($request->getParsedBody()['scope'] == 'be' && strtolower($user->level) == 'customer'){
                                return response()->json(['status' => 'fail', 'messages' => "You don't have access in this app"]);
                            }
                        }else{
                            return response()->json(['status' => 'fail', 'messages' => 'Incompleted input']);
                        }
                    }
                }
            }
            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );

        }
        catch (OAuthServerException $exception) {
            //return error message

            if($exception->getCode() == 6){
                return response()->json(['status' => 'fail', 'messages' => 'Pin tidak sesuai.']);
            }

            return $this->withErrorHandling(function () use($exception) {
                throw $exception;
            });
        }
    }
}