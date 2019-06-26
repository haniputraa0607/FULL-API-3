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
        try {
            if(isset($request->getParsedBody()['username']) && isset($request->getParsedBody()['password'])){
                
                if(Auth::attempt(['phone' => $request->getParsedBody()['username'], 'password' => $request->getParsedBody()['password']])){
                    $user = User::where('phone', $request->getParsedBody()['username'])->first();
                    if($user){
                        if($user->is_suspended == '1'){
                            return response()->json(['status' => 'fail', 'messages' => 'Maaf, akun Anda sedang di-suspend']);
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