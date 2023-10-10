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

        return (array)$content[0];
    }

    public function decodeToken($token)
    {
        $jwt = new JWT();
        $jwtSecretKey = 'SecretKeyJWT';
        $decodedToken = $jwt->decode($token, $jwtSecretKey, false);
        return (array)$decodedToken;
    }

}