<?php namespace App\Package\Signature\Guards;

use App\Package\Signature\Exceptions\SignatureKeyException;

class CheckKey implements Guard
{

    /**
     * Check to ensure the auth parameters
     * satisfy the rule of the guard
     *
     * @param array  $auth
     * @param array  $signature
     * @throws SignatureKeyException
     * @return bool
     */
    public function check(array $auth, array $signature)
    {
        if (! isset($auth['pid'])) {
            throw new SignatureKeyException('The authentication key has not been set');
        }

        if ($auth['pid'] !== $signature['pid']) {
            throw new SignatureKeyException('The authentication key is not valid');
        }

        return true;
    }
}
