<?php
/**
 * Service registry API
 * User: moyo
 * Date: 2018/11/23
 * Time: 10:57 AM
 */

namespace Carno\HRPC\Serviced;

use Carno\Net\Address;
use Carno\Promise\Promised;

interface Registry
{
    /**
     * @param Address $advertise
     * @return Promised
     */
    public function register(Address $advertise) : Promised;

    /**
     * @return Promised
     */
    public function deregister() : Promised;
}
