<?php

namespace Letscms;

defined('ABSPATH') || exit;

use Nowakowskir\JWT\TokenDecoded;
use Nowakowskir\JWT\TokenEncoded;
use Nowakowskir\JWT\JWT;

/**
 * Jwt auth class
 */
class LetscmsJwtAuth
{
    public function generateToken($data)
    {
        $tokenDecoded = new TokenDecoded([], (array) $data);
        $privateKey = file_get_contents(LWRA_ABSPATH . '/RS256/jwtRS256.key');
        $tokenEncoded = $tokenDecoded->encode($privateKey, JWT::ALGORITHM_RS256);
        return $token = $tokenEncoded->__toString();
    }

    public static function verifyToken($token)
    {
        try {
            $tokenEncoded = new TokenEncoded($token);
            $publicKey = file_get_contents(LWRA_ABSPATH . '/RS256/jwtRS256.key.pub');
            $tokenEncoded->validate($publicKey, JWT::ALGORITHM_RS256);
            $tokenDecoded = $tokenEncoded->decode();
            $data = $tokenDecoded->getPayload();
            return (object) $data;
        } catch (\Exception $e) {
            return false;
        }
    }
}
