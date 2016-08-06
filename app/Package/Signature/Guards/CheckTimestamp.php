<?php namespace App\Package\Signature\Guards;

use App\Package\Signature\Exceptions\SignatureTimestampException;

class CheckTimestamp implements Guard
{
    /**
     * @var int
     */
    private $grace;

    /**
     * Create a new CheckTimestamp Guard
     *
     * @param int $grace
     * @return void
     */
    public function __construct($grace = 600000)
    {
        $this->grace = $grace;
    }

    /**
     * Check to ensure the auth parameters
     * satisfy the rule of the guard
     *
     * @param array  $auth
     * @param array  $signature
     * @throws SignatureTimestampException
     * @return bool
     */
    public function check(array $auth, array $signature)
    {
        if (! isset($auth['time'])) {
            throw new SignatureTimestampException('The timestamp has not been set');
        }

        if (abs($auth['time'] - time()) >= $this->grace) {
            throw new SignatureTimestampException('The timestamp is invalid');
        }

        return true;
    }
}
