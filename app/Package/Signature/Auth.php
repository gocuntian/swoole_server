<?php namespace App\Package\Signature;

use App\Helper\LoggerHelper;

class Auth
{

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $auth = [
        'pid',
        'pv',
        'time',
        'sig'
    ];

    /**
     * Create a new Auth instance
     *
     * @param array $params
     * @param array $guards
     * @return void
     */

    public function __construct(array $params, array $guards)
    {
        $this->params = $this->init($params);
        $this->guards = $guards;
        //$loggerHelper  = new LoggerHelper();
        //$this->logger  = $loggerHelper->getInstance();
    }

    public function init($params){
        $data = $params['data'];
        unset($params['data']);
        return array_merge($params,$data);
    }

    /**
     * Attempt to authenticate a request
     *
     * @param Token  $token
     * @return bool
     */
    public function attempt(Token $token)
    {
        $auth = $this->getAuthParams();
        $body = $this->getBodyParams();
        $time = isset($auth['time'])?$auth['time']:time();
        $request   = new Request($body, $time);
        $signature = $request->sign($token);

        foreach ($this->guards as $guard) {
            $guard->check($auth, $signature);
        }

        return true;
    }

    /**
     * Get the auth params
     *
     * @return array
     */
    private function getAuthParams()
    {
        return array_intersect_key($this->params, array_flip($this->auth));
    }

    /**
     * Get the body params
     *
     * @return array
     */
    private function getBodyParams()
    {
        return array_diff_key($this->params, array_flip($this->auth));
    }

}
