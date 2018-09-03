<?php
/**
 * Traffic monitor
 * User: moyo
 * Date: 23/10/2017
 * Time: 10:11 AM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\HRPC\Client\Chips\ErrorsClassify;
use Carno\Monitor\Metrics;
use Carno\Monitor\Metrics\Counter;
use Carno\Monitor\Metrics\Gauge;
use Carno\Monitor\Metrics\Histogram;
use Carno\RPC\Protocol\Request;
use Carno\RPC\Protocol\Response;
use Throwable;

class TrafficMonitor implements Layered
{
    use ErrorsClassify;

    /**
     * @var Counter
     */
    private $rxBytes = null;

    /**
     * @var Counter
     */
    private $txBytes = null;

    /**
     * @var Counter
     */
    private $ttRequests = null;

    /**
     * @var Histogram
     */
    private $ttResponses = null;

    /**
     * @var Counter
     */
    private $ttExceptions = null;

    /**
     * @var Gauge
     */
    private $ttProcessing = null;

    /**
     * TrafficMonitor constructor.
     */
    public function __construct()
    {
        $this->rxBytes = Metrics::counter()->named('rpc.rx.bytes');
        $this->txBytes = Metrics::counter()->named('rpc.tx.bytes');
        $this->ttRequests = Metrics::counter()->named('rpc.requests.all');
        $this->ttResponses = Metrics::histogram()->named('rpc.responses.time')->fixed(5, 20, 50, 200, 500, 1000);
        $this->ttExceptions = Metrics::counter()->named('rpc.exceptions.all');
        $this->ttProcessing = Metrics::gauge()->named('rpc.processing.now');
    }

    /**
     * @param Request $request
     * @param Context $ctx
     * @return Request
     */
    public function inbound($request, Context $ctx) : Request
    {
        $this->requestBegin($ctx);
        $this->rxBytes->inc(strlen($request->getPayload()));
        $this->ttRequests->inc();
        $this->ttProcessing->inc();
        return $request;
    }

    /**
     * @param Response $response
     * @param Context $ctx
     * @return Response
     */
    public function outbound($response, Context $ctx) : Response
    {
        $this->txBytes->inc(strlen($response->getPayload()));
        $this->ttProcessing->dec();
        $this->requestEnd($ctx);
        return $response;
    }

    /**
     * @param Throwable $e
     * @param Context $ctx
     * @throws Throwable
     */
    public function exception(Throwable $e, Context $ctx) : void
    {
        $this->ttProcessing->dec();
        $this->isGenericException($e) || $this->ttExceptions->inc();
        $this->requestEnd($ctx);
        throw $e;
    }

    /**
     * @param Context $ctx
     */
    private function requestBegin(Context $ctx) : void
    {
        $ctx->set('tm-r-begin', microtime(true));
    }

    /**
     * @param Context $ctx
     */
    private function requestEnd(Context $ctx) : void
    {
        $this->ttResponses->observe((microtime(true) - $ctx->get('tm-r-begin') ?? 0) * 1000);
    }
}
