<?php
/**
 * Request logging
 * User: moyo
 * Date: 12/10/2017
 * Time: 6:29 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\RPC\Protocol\Request;
use Carno\RPC\Protocol\Response;
use Throwable;

class RequestLogger implements Layered
{
    /**
     * ctx vars
     */
    private const REQUEST = 'log-request';
    private const START_TIME = 'log-start';

    /**
     * @param Request $request
     * @param Context $ctx
     * @return Request
     */
    public function inbound($request, Context $ctx) : Request
    {
        $ctx->set(self::REQUEST, $request);
        $ctx->set(self::START_TIME, microtime(true));
        return $request;
    }

    /**
     * @param Response $response
     * @param Context $ctx
     * @return Response
     */
    public function outbound($response, Context $ctx) : Response
    {
        $this->requestFIN($ctx, null);
        return $response;
    }

    /**
     * @param Throwable $e
     * @param Context $ctx
     * @throws Throwable
     */
    public function exception(Throwable $e, Context $ctx) : void
    {
        $this->requestFIN($ctx, $e);
        throw $e;
    }

    /**
     * @param Context $ctx
     * @param Throwable $e
     */
    private function requestFIN(Context $ctx, Throwable $e = null) : void
    {
        /**
         * @var Request $rpc
         * @var float $start
         */
        $rpc = $ctx->get(self::REQUEST);
        $start = $ctx->get(self::START_TIME);

        $meta = [
            'id' => $rpc ? $rpc->identify() : 'unknown',
            'cost' => $start ? intval((microtime(true) - $start) * 1000) : 0,
        ];

        if ($e) {
            $meta = array_merge($meta, [
                'err' => get_class($e) . '::' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            logger('hrpc')->notice('Request failed', $meta);
        } else {
            logger('hrpc')->debug('Request finished', $meta);
        }
    }
}
