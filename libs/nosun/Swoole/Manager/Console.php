<?php namespace Nosun\Swoole\Manager;

class Console
{
    /**
     * 改变进程的用户ID
     * @param $user
     */

    static function changeUser($user)
    {
		if (!function_exists('posix_getpwnam'))
		{
			trigger_error(__METHOD__.": require posix extension.");
			return;
		}

        $user = posix_getpwnam($user);

        if($user)
        {
            posix_setuid($user['uid']);
            posix_setgid($user['gid']);
        }
    }
    

    /**
     * 设置进程名称
     * @param $name
     */

    static function setProcessName($name)
    {
        if (function_exists('cli_set_process_title'))
        {
            cli_set_process_title($name);
        }
        else if(function_exists('swoole_set_process_name'))
        {
            swoole_set_process_name($name);
        }
        else
        {
            trigger_error(__METHOD__." failed. require cli_set_process_title or swoole_set_process_name.");
        }
    }
}