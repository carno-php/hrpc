<?php
/**
 * Server detecting of custom routes
 * User: moyo
 * Date: 2018/4/20
 * Time: 3:29 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Contracts\Defined;
use Carno\HRPC\Client\Handlers\RoutesMarking;
use Carno\HTTP\Standard\Request;
use Throwable;

class RoutesDetecting implements Layered
{
    public function inbound($request, Context $ctx)
    {
        /**
         * @var Request $http
         */

        $http = $ctx->get(ServerWrapper::REQUESTING);

        if ($http->hasHeader(Defined::X_ROUTE_TAGS)) {
            $ctx->set(RoutesMarking::FLAG, $http->getHeader(Defined::X_ROUTE_TAGS));
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
