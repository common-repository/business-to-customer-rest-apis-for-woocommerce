<?php
namespace Nowakowskir\JWT;

use \Exception;
use Nowakowskir\JWT\Base64Url;
use Nowakowskir\JWT\Validation;
use Nowakowskir\JWT\Exceptions\SigningFailedException;
use Nowakowskir\JWT\Exceptions\IntegrityViolationException;
use Nowakowskir\JWT\Exceptions\UnsupportedAlgorithmException;

/**
 * This class contains basic set of methods for handling JSON Web Tokens (JWT).
 *
 * @author   Radosław Nowakowski <nowakowski.r@gmail.com>
 * @license  http://opensource.org/licenses/BSD-3-Clause 3-clause BSD
 * @link     https://github.com/nowakowskir/php-jwt
 */
class JWT
{

    /**
     * List of available algorithm keys.
     */
    const ALGORITHM_HS256 = 'HS256';
    const ALGORITHM_HS384 = 'HS384';
    const ALGORITHM_HS512 = 'HS512';
    const ALGORITHM_RS256 = 'RS256';
    const ALGORITHM_RS384 = 'RS384';
    const ALGORITHM_RS512 = 'RS512';
    
    /**
     * Default algorithm key that will be used when encoding token in case no algorithm was provided in token's header nor as parameter to encode method.
     */
    const DEFAULT_ALGORITHM = self::ALGORITHM_HS256;
    
    /**
     * Mapping of available algorithm keys with their types and target algorithms.
     */
    const ALGORITHMS = [
        self::ALGORITHM_HS256 => ['hash_hmac', 'SHA256'],
        self::ALGORITHM_HS384 => ['hash_hmac', 'SHA384'],
        self::ALGORITHM_HS512 => ['hash_hmac', 'SHA512'],
        self::ALGORITHM_RS256 => ['openssl', 'SHA256'],
        self::ALGORITHM_RS384 => ['openssl', 'SHA384'],
        self::ALGORITHM_RS512 => ['openssl', 'SHA512'],
    ];

    /**
     * Decodes encoded token.
     * 
     * @param TokenEncoded  $tokenEncoded   Encoded token
     * 
     * @return TokenDecoded
     */
    public static function decode(TokenEncoded $tokenEncoded): TokenDecoded
    {
        return new TokenDecoded(json_decode(Base64Url::decode($tokenEncoded->getHeader()), true),
            json_decode(Base64Url::decode($tokenEncoded->getPayload()), true));
    }

    /**
     * Encodes decoded token.
     * 
     * @param TokenDecoded  $tokenDecoded   Decoded token
     * @param string        $key            Key used to sign the token
     * @param string|null   $algorithm      Force algorithm even if token's header exists
     * 
     * @return TokenEncoded
     */
    public static function encode(TokenDecoded $tokenDecoded, string $key, ?string $algorithm = null): TokenEncoded
    {
        $header = array_merge($tokenDecoded->getHeader(), [
            'typ' => array_key_exists('typ', $tokenDecoded->getHeader()) ? $tokenDecoded->getHeader()['typ'] : 'JWT',
            'alg' => $algorithm ? $algorithm : (array_key_exists('alg', $tokenDecoded->getHeader()) ? $tokenDecoded->getHeader()['alg'] : self::DEFAULT_ALGORITHM),
        ]);

        $elements = [];
        $elements[] = Base64Url::encode(json_encode($header));
        $elements[] = Base64Url::encode(json_encode($tokenDecoded->getPayload()));

        $signature = self::sign(implode('.', $elements), $key, $header['alg']);
        $elements[] = Base64Url::encode($signature);

        return new TokenEncoded(implode('.', $elements));
    }

    
    /**
     * Generates signature for given message.
     * 
     * @param string $message   Message to sign, which is base64 encoded values of header and payload separated by dot
     * @param string $key       Key used to sign the token
     * @param string $algorithm Algorithm to use for signing the token
     * 
     * @return string
     * 
     * @throws SigningFailedException
     * @throws SigningFailedException
     */
    protected static function sign(string $message, string $key, string $algorithm): string
    {
        list($function, $type) = self::getAlgorithmData($algorithm);

        switch ($function) {
            case 'hash_hmac':
                try {
                    $signature = hash_hmac($type, $message, $key, true);
                } catch (Exception $e) {
                    throw new SigningFailedException(sprintf('Signing failed: %s', $e->getMessage()));
                }
                if ($signature === false) {
                    throw new SigningFailedException('Signing failed');
                }
                return $signature;
                break;
            case 'openssl':
                $signature = '';
                
                try {
                    $sign = openssl_sign($message, $signature, $key, $type);
                } catch (Exception $e) {
                    throw new SigningFailedException(sprintf('Signing failed: %s', $e->getMessage()));
                }
                
                if (! $sign) {
                    throw new SigningFailedException('Signing failed');
                }
                
                return $signature;
                break;
            default:
                throw new UnsupportedAlgorithmException('Invalid function');
                break;
        }
    }

    /**
     * Validates token's using provided key.
     * 
     * This method should be used to check if given token is valid.
     * 
     * Following things should be verified:
     * - if token contains algorithm defined in its header
     * - if token integrity is met using provided key
     * - if token contains expiration date (exp) in its payload - current time against this date
     * - if token contains not before date (nbf) in its payload - current time against this date
     * - if token contains issued at date (iat) in its payload - current time against this date
     * 
     * @param TokenEncoded  $tokenEncoded   Encoded token
     * @param string        $key            Key used to signature verification
     * @param string|null   $algorithm      Force algorithm to signature verification (recommended)
     * @param int|null      $leeway         Some optional period to avoid clock synchronization issues
     * @param array|null    $key            Claims to be excluded from validation
     * 
     * @return boolean
     * 
     * @throws IntegrityViolationException
     * @throws UnsupportedAlgorithmException
     */
    public static function validate(TokenEncoded $tokenEncoded, string $key, ?string $algorithm, ?int $leeway = null, ?array $claimsExclusions = null): void
    {
        $tokenDecoded = self::decode($tokenEncoded);

        $signature = Base64Url::decode($tokenEncoded->getSignature());
        $header = $tokenDecoded->getHeader();
        $payload = $tokenDecoded->getPayload();

        list($function, $type) = self::getAlgorithmData($algorithm ?? $header['alg']);

        switch ($function) {
            case 'hash_hmac':
                if (hash_equals($signature, hash_hmac($type, $tokenEncoded->getMessage(), $key, true)) !== true) {
                    throw new IntegrityViolationException('Invalid signature');
                }
                break;
            case 'openssl':
                if (openssl_verify($tokenEncoded->getMessage(), $signature, $key, $type) !== 1) {
                    throw new IntegrityViolationException('Invalid signature');
                }
                break;
            default:
                throw new UnsupportedAlgorithmException('Unsupported algorithm type');
                break;
        }
           
        if (array_key_exists('exp', $payload)) {
            Validation::checkExpirationDate($payload['exp'], $leeway);
        }
        
        if (array_key_exists('nbf', $payload)) {
            Validation::checkNotBeforeDate($payload['nbf'], $leeway);
        }
    }
    
    /**
     * Transforms algorithm key into array containing its type and target algorithm.
     * 
     * @param string    $algorithm     Algorithm key
     * 
     * @return array
     * 
     * @throws UnsupportedAlgorithmException
     */
    public static function getAlgorithmData(string $algorithm): array
    {
        Validation::checkAlgorithmSupported($algorithm);

        return self::ALGORITHMS[$algorithm];
    }

}
