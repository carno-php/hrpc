<?php
/**
 * HTTP Request incoming (server accepted)
 * User: moyo
 * Date: 23/11/2017
 * Time: 12:17 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Consul\Types\Tagging;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Contracts\Defined;
use Carno\HTTP\Server\Connection;
use Carno\HTTP\Standard\Request as HRequest;
use Carno\RPC\Protocol\Request;
use Carno\Tracing\Contracts\Platform;
use Carno\Tracing\Contracts\Vars\EXT;
use Carno\Tracing\Contracts\Vars\FMT;
use Carno\Tracing\Contracts\Vars\TAG;
use Carno\Tracing\Standard\Endpoint;
use Carno\Tracing\Utils\SpansCreator;
use Throwable;

class TracedIncoming implements Layered
{
    use SpansCreator;

    /**
     * @var Platform
     */
    private $platform = null;

    /**
     * @var string
     */
    private $tagged = '';

    /**
     * TracedIncoming constructor.
     * @param Platform $platform
     * @param Tagging $tagging
     */
    public function __construct(Platform $platform, Tagging $tagging = null)
    {
        $this->platform = $platform;
        $tagging && $this->tagged = implode(',', $tagging->getTags());
    }

    /**
     * @param Request $request
     * @param Context $ctx
     * @return Request
     */
    public function inbound($request, Context $ctx) : Request
    {
        /**
         * @var Connection $conn
         * @var HRequest $http
         */
        $conn = $ctx->get(ServerWrapper::CONNECTION);
        $http = $ctx->get(ServerWrapper::REQUESTING);

        $this->newSpan(
            $ctx,
            sprintf('%s.%s', $request->service(), $request->method()),
            [
                TAG::SPAN_KIND => TAG::SPAN_KIND_RPC_SERVER,
                EXT::LOCAL_ENDPOINT => new Endpoint($request->server(), $conn->local()),
                EXT::REMOTE_ENDPOINT => new Endpoint($request->server(), $conn->remote()),
                TAG::USER_AGENT => $http->getHeaderLine('User-Agent'),
                TAG::CONTENT_TYPE => $http->getHeaderLine('Content-Type'),
                TAG::ROUTE_TAGS => $http->getHeaderLine(Defined::X_ROUTE_TAGS),
                TAG::ENV_TAGS => $this->tagged,
            ],
            [
                TAG::HTTP_URL => (string) $http->getUri(),
                TAG::HTTP_METHOD => $http->getMethod(),
            ],
            FMT::HTTP_HEADERS,
            $http,
            null,
            $this->platform
        );

        return $request;
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
     * @throws Throwable
     */
    public function exception(Throwable $e, Context $ctx) : void
    {
        throw $e;
    }
}
