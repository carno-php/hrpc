<?php
/**
 * Server unpacking of attachments
 * User: moyo
 * Date: 19/03/2018
 * Time: 9:53 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Handlers\ContextPacking;
use Carno\HTTP\Standard\Request;
use Throwable;

class ContextUnpacking implements Layered
{
    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @param string ...$allowed
     */
    public function keys(string ...$allowed) : void
    {
        $this->keys = $allowed;
    }

    public function inbound($request, Context $ctx)
    {
        /**
         * @var Request $http
         */
        $http = $ctx->get(ServerWrapper::REQUESTING);

        if ($dat = $http->getHeaderLine(ContextPacking::HTTP_HEADER)) {
            if ($attachments = json_decode(base64_decode($dat), true)) {
                foreach ($attachments as $key => $val) {
                    in_array($key, $this->keys) && $ctx->set($key, $val);
                }
            }
        }

        return $request;
    }

    public function outbound($response, Context $ctx)
    {
        return $response;
    }

    public function exception(Throwable $e, Context $ctx)
    {
        throw $e;
    }
}
