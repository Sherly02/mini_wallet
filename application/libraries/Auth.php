<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth {
    private $userData = [];
    
	public function getToken($userData)
	{
        $jwt = new JWT();
        $jwtSecretKey = 'SecretKeyJWT';
        $token = $jwt->encode($userData, $jwtSecretKey);
        return $token;
	}

    public function getTokenContent($token)
    {
        $token = explode(' ', $token)[1];
        $content = (array)$this->decodeToken($token);

        if (isset($content['error'])) {
            return $content;
        }

        return array_key_exists(0, (array)$content) ? (array)$content[0] : (array)$content;
    }

    public function decodeToken($token)
    {
        $jwt = new JWT();
        $jwtSecretKey = 'SecretKeyJWT';
        $decodedToken = $jwt->decode($token, $jwtSecretKey, false);
        if (array_key_exists('owned_by', (array)$decodedToken)){
            $decodedToken->customer_xid = $decodedToken->owned_by;
        }
        return (array)$decodedToken;
    }

}