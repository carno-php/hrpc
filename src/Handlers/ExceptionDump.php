<?php
/**
 * Exception handler
 * User: moyo
 * Date: 11/01/2018
 * Time: 5:11 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Chips\ErrorsClassify;
use Throwable;

class ExceptionDump implements Layered
{
    use ErrorsClassify;

    /**
     * @param mixed $message
     * @param Context $ctx
     * @return mixed
     */
    public function inbound($message, Context $ctx)
    {
        return $message;
    }

    /**
     * @param mixed $message
     * @param Context $ctx
     * @return mixed
     */
    public function outbound($message, Context $ctx)
    {
        return $message;
    }

    /**
     * @param Throwable $e
     * @param Context $ctx
     * @return void
     */
    public function exception(Throwable $e, Context $ctx)
    {
        $this->isGenericException($e) || debug() && dump($e);
    }
}
