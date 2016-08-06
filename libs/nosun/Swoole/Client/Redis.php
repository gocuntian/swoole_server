<?php namespace Nosun\Swoole\Client;

use Mockery\Exception;
use Noodlehaus\Config;

class Redis {

	protected $_redis;
	protected $_config;

	public function __construct(){
		$conf = Config::load(CONFPATH.'redis.php');
		$this->_config['host'] = $conf->get('redis.host')?$conf->get('redis.host'):'127.0.0.1';
		$this->_config['port'] = $conf->get('redis.port')?$conf->get('redis.port'):6379;
		$this->_config['timeout'] =$conf->get('redis.timeout')?$conf->get('redis.timeout'):0.0;
		$this->_config['pass'] = $conf->get('redis.pass');
		$this->_config['timeout'] =$conf->get('redis.database');
		$this->_config['pconnect'] =$conf->get('redis.pconnect');
		unset($conf);
		try {
			if ($this->_redis)
			{
				unset($this->_redis);
			}
			$this->_redis = new \Redis();
			$this->connect();
		}catch (\RedisException $e){
			//
		}
	}

	protected function connect(){

		if ($this->_config['pconnect'])
		{
			$this->_redis->pconnect($this->_config['host'], $this->_config['port'], $this->_config['timeout']);
		}
		else
		{
			$this->_redis->connect($this->_config['host'], $this->_config['port'], $this->_config['timeout']);
		}
		if(!empty($this->_config['pass'])){
			$this->_redis->auth($this->_config['pass']);
		}
		if (!empty($this->_config['database']))
		{
			$this->_redis->select($this->_config['database']);
		}
	}

    /**
	 * Dynamically make a Redis command.
     *
     * @param $method
     * @param $parameters
     * @return bool|mixed
     * @throws \Exception
     * @throws \RedisException
     */

	public function __call($method, $parameters)
	{
		$reConnect = false;
		while (1)
		{
			try
			{
				$result = call_user_func_array(array($this->_redis, $method), $parameters);
			}
			catch (\RedisException $e)
			{
				//已重连过，仍然报错
				if ($reConnect)
				{
					throw $e;
				}
				// Todo make error log;
				$this->_redis->close();
				$this->connect();
				$reConnect = true;
				continue;
			}
			return $result;
		}
		//不可能到这里
		return false;
	}

}
