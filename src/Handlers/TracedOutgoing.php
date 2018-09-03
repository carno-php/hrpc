<?php
/**
 * HTTP response outgoing (server returned)
 * User: moyo
 * Date: 23/11/2017
 * Time: 12:20 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Chips\ErrorsClassify;
use Carno\HTTP\Standard\Response;
use Carno\Tracing\Contracts\Vars\TAG;
use Carno\Tracing\Utils\SpansCreator;
use Carno\Tracing\Utils\SpansExporter;
use Throwable;

class TracedOutgoing implements Layered
{
    use SpansCreator, SpansExporter, ErrorsClassify;

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
     * @param Response $response
     * @param Context $ctx
     * @return Response
     */
    public function outbound($response, Context $ctx) : Response
    {
        /**
         * @var Response $http
         */
        $http = $ctx->get(ServerWrapper::RESPONDING);

        $this->closeSpan($ctx, [TAG::HTTP_STATUS_CODE => $http->getStatusCode()]);

        $this->spanToHResponse($ctx, $http);

        return $response;
    }

    /**
     * @param Throwable $e
     * @param Context $ctx
     * @throws Throwable
     */
    public function exception(Throwable $e, Context $ctx) : void
    {
        /**
         * @var Response $http
         */

        if ($http = $ctx->get(ServerWrapper::RESPONDING)) {
            $code = $http->getStatusCode();
            $this->spanToHResponse($ctx, $http);
        }

        $this->isGenericException($e)
            ? $this->closeSpan($ctx, [TAG::HTTP_STATUS_CODE => $code ?? 200])
            : $this->errorSpan($ctx, $e)
        ;

        throw $e;
    }
}
