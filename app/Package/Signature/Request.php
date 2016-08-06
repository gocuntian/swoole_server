<?php namespace App\Package\Signature;

use App\Helper\LoggerHelper;

class Request
{

    const VERSION = '1.0';

    /**
     * @var array
     */
    private $params;

    /**
     * @var integer
     */
    private $timestamp;

    /**
     * Create a new Request
     *
     * @param array $params
     * @param integer $timestamp
     */
    public function __construct(array $params, $timestamp = null)
    {
        $this->params    = $params;
        $this->timestamp = $timestamp ?: time();
        $loggerHelper  = new LoggerHelper();
        $this->logger  = $loggerHelper->getInstance();
    }

    /**
     * Sign the Request with a Token
     *
     * @param Token  $token
     * @return array
     */
    public function sign(Token $token)
    {
        $auth = [
            'pv'   => self::VERSION,
            'pid'  => $token->key(),
            'time' => $this->timestamp,
        ];

        $payload = $this->payload($auth, $this->params);

        $signature = $this->signature($payload, $token->secret());

        $auth['sig'] = $signature;

        return $auth;
    }

    /**
     * Create the payload
     *
     * @param array $auth
     * @param array $params
     * @return array
     */
    private function payload(array $auth, array $params)
    {
        $payload = array_merge($auth, $params);
        $payload = array_change_key_case($payload, CASE_LOWER);

        ksort($payload);

        return $payload;
    }

    /**
     * Create the signature
     *
     * @param array $payload
     * @param string $secret
     * @return string
     */
    private function signature(array $payload, $secret)
    {
        $payload = http_build_query($payload);
        $payload = urldecode($payload);
        $this->logger->info('payload:'.$payload);
        $this->logger->info('secret:'.$secret);
        $this->logger->info(hash_hmac('sha256', $payload, $secret));
        return hash_hmac('sha256', $payload, $secret);
    }
}
