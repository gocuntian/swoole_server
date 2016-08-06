<?php namespace App\Package\Signature\Guards;

use App\Package\Signature;

interface Guard
{

    /**
     * Check to ensure the auth parameters
     * satisfy the rule of the guard
     *
     * @param array  $auth
     * @param array  $signature
     * @return bool
     */
    public function check(array $auth, array $signature);
}
