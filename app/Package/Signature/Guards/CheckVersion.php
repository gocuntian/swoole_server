<?php namespace App\Package\Signature\Guards;

use App\Package\Signature;
use App\Package\Signature\Exceptions\SignatureVersionException;

class CheckVersion implements Guard
{

    /**
     * Check to ensure the auth parameters
     * satisfy the rule of the guard
     *
     * @param array  $auth
     * @param array  $signature
     * @throws SignatureVersionException
     * @return bool
     */
    public function check(array $auth, array $signature)
    {
        if (! isset($auth['pv'])) {
            throw new SignatureVersionException('The version has not been set');
        }

        if ($auth['pv'] !== $signature['pv']) {
            throw new SignatureVersionException('The signature version is not correct');
        }

        return true;
    }
}
