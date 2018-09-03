<?php
/**
 * Request traffic shaping
 * User: moyo
 * Date: 17/10/2017
 * Time: 5:18 PM
 */

namespace Carno\HRPC\Handlers;

use Carno\Chain\Layered;
use Carno\Coroutine\Context;
use Carno\Monitor\Metrics;
use Carno\Monitor\Metrics\Gauge;
use Carno\Monitor\Ticker;
use Carno\Promise\Promised;
use Carno\RPC\Protocol\Request;
use Carno\RPC\Protocol\Response;
use Carno\Shaping\Options;
use Carno\Shaping\Shaper;
use Throwable;

class TrafficShaping implements Layered
{
    /**
     * @var Shaper
     */
    private $shaper = null;

    /**
     * TrafficShaping constructor.
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->shaper = $shaper = new Shaper($options);

        Ticker::new([
            Metrics::gauge()->named('shaper.bucket.tokens'),
            Metrics::gauge()->named('shaper.acquire.waits'),
        ], static function (Gauge $tokens, Gauge $waits) use ($shaper) {
            $tokens->set($shaper->tokens());
            $waits->set($shaper->waits());
        });
    }

    /**
     * @param Request $request
     * @param Context $ctx
     * @return Promised|Request
     */
    public function inbound($request, Context $ctx)
    {
        if ($this->shaper->acquired()) {
            return $request;
        }

        return $this->shaper->queued()->then(static function () use ($request) {
            return $request;
        });
    }

    /**
     * @param Response $response
     * @param Context $ctx
     * @return Response
     */
    public function outbound($response, Context $ctx)
    {
        return $response;
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
