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
        return (array)$this->decodeToken($token)[0];
    }

    public function decodeToken($token)
    {
        $jwt = new JWT();
        $jwtSecretKey = 'SecretKeyJWT';
        $decodedToken = $jwt->decode($token, $jwtSecretKey, false);
        return $decodedToken;
    }

}