<?php
/**
 * Server unavailable forced
 * User: moyo
 * Date: 2018/7/18
 * Time: 2:51 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Exception\ServerUnavailableException;
use Throwable;

class ServerUnavailable implements Layered
{
    public function inbound($message, Context $ctx)
    {
        throw new ServerUnavailableException;
    }

    public function outbound($message, Context $ctx)
    {
        return $message;
    }

    public function exception(Throwable $e, Context $ctx)
    {
        throw $e;
    }
}
