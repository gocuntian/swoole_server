<?php namespace App\Package\Signature\Guards;

use App\Package\Signature\Exceptions\SignatureSignatureException;

class CheckSignature implements Guard
{

    /**
     * Check to ensure the auth parameters
     * satisfy the rule of the guard
     *
     * @param array  $auth
     * @param array  $signature
     * @throws SignatureSignatureException
     * @return bool
     */
    public function check(array $auth, array $signature)
    {
        if (! isset($auth['sig'])) {
            throw new SignatureSignatureException('The signature has not been set');
        }

        if ($auth['sig'] !== $signature['sig']) {
            throw new SignatureSignatureException('The signature is not valid');
        }

        return true;
    }
}
