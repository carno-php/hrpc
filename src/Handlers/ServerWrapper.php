<?php
/**
 * HTTP server wrapper
 * User: moyo
 * Date: 29/09/2017
 * Time: 3:42 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Chips\ErrorsHelper;
use Carno\HRPC\Client\Contracts\Defined;
use Carno\HRPC\Exception\ServerUnavailableException;
use Carno\HTTP\Server\Connection;
use Carno\HTTP\Standard\Response as HResponse;
use Carno\RPC\Errors\GenericError;
use Carno\RPC\Exception\IllegalRequestSyntaxException;
use Carno\RPC\Exception\RemoteLogicException;
use Carno\RPC\Exception\RemoteSystemException;
use Carno\RPC\Exception\RequestTargetNotFoundException;
use Carno\RPC\Protocol\Request;
use Carno\RPC\Protocol\Response;
use Throwable;

class ServerWrapper implements Layered
{
    use ErrorsHelper;

    /**
     * operated connection
     */
    public const CONNECTION = 'conn-session';

    /**
     * request/response for connection
     */
    public const REQUESTING = 'conn-request';
    public const RESPONDING = 'conn-response';

    /**
     * @param Connection $ingress
     * @param Context $ctx
     * @return Request
     */
    public function inbound($ingress, Context $ctx) : Request
    {
        $ctx->set(self::CONNECTION, $ingress);
        $ctx->set(self::REQUESTING, $sr = $ingress->request());

        switch ($sr->getMethod()) {
            case 'POST':
                $content = $sr->getHeaderLine('Content-Type');
                $payload = (string) $sr->getBody();
                break;
            default:
                throw new IllegalRequestSyntaxException("METHOD:{$sr->getMethod()}");
        }

        switch (substr($path = $sr->getUri()->getPath(), 1, ($crp = strpos($path, '/', 1)) - 1)) {
            case 'invoke':
                $uri = substr($path, $crp + 1);
                goto COMMAND_IVK;
                break;
            default:
                throw new IllegalRequestSyntaxException("PATH:{$path}");
        }

        COMMAND_IVK:

        list($service, $method) = explode('/', $uri);

        return
            (new Request($ingress->serviced() ?: $sr->getHeaderLine('Host'), ucfirst($service), lcfirst($method)))
                ->setJsonc($content === Defined::V_TYPE_JSON)
                ->setPayload($payload)
        ;
    }

    /**
     * @param Response $response
     * @param Context $ctx
     * @return HResponse
     */
    public function outbound($response, Context $ctx) : HResponse
    {
        if ($response instanceof Response) {
            $http = new HResponse(
                200,
                [
                    'Content-Type' => $response->isJsonc() ? Defined::V_TYPE_JSON : Defined::V_TYPE_PROTO,
                ],
                $response->getPayload()
            );
        } else {
            $http = new HResponse(500, $this->errorHeaders('Invalid service response'));
        }

        $ctx->set(self::RESPONDING, $http);

        return $http;
    }

    /**
     * @param Throwable $e
     * @param Context $ctx
     * @throws Throwable
     */
    public function exception(Throwable $e, Context $ctx) : void
    {
        /**
         * @var Connection $ingress
         */
        $ingress = $ctx->get(self::CONNECTION);

        if ($e instanceof IllegalRequestSyntaxException) {
            $r = new HResponse(400);
        } elseif ($e instanceof RequestTargetNotFoundException) {
            $r = new HResponse(404);
        } elseif ($e instanceof GenericError) {
            $r = new HResponse(
                $e instanceof RemoteLogicException ? 500 : 200,
                $this->errorHeaders($e->getMessage(), $e->getCode())
            );
        } elseif ($e instanceof ServerUnavailableException) {
            $r = new HResponse(503);
        } else {
            $r = new HResponse(
                500,
                $e instanceof RemoteSystemException
                    ? $this->errorHeaders($e->getMessage(), $e->getCode())
                    : $this->errorHeaders(
                        sprintf('%s::%s::%s', $ingress->serviced(), get_class($e), $e->getMessage()),
                        $e->getCode()
                    )
            );
        }

        $ctx->set(self::RESPONDING, $r);

        throw $e;
    }
}
