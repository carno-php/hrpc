<?php
/**
 * Service registry via "consul"
 * User: moyo
 * Date: 13/12/2017
 * Time: 11:21 AM
 */

namespace Carno\HRPC\Serviced;

use Carno\Consul\Registry as CRegistry;
use Carno\Consul\Types\Agent;
use Carno\Consul\Types\Result;
use Carno\Consul\Types\Service;
use Carno\Consul\Types\Tagging;
use Carno\Net\Address;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\RPC\Service\Router;

class ConsulRG implements Registry
{
    /**
     * @var Agent
     */
    private $cAgent = null;

    /**
     * @var Router
     */
    private $dRouter = null;

    /**
     * @var Tagging
     */
    private $dTagging = null;

    /**
     * @var Service[]
     */
    private $sRegistered = [];

    /**
     * Register constructor.
     * @param Agent $agent
     * @param Router $router
     * @param Tagging $tagging
     */
    public function __construct(Agent $agent, Router $router, Tagging $tagging)
    {
        $this->cAgent = $agent;
        $this->dRouter = $router;
        $this->dTagging = $tagging;
    }

    /**
     * @param Address $advertise
     * @return Promised
     */
    public function register(Address $advertise) : Promised
    {
        $waits = [];

        foreach ($this->dRouter->servers() as $server) {
            $this->sRegistered[] = $service = (new CRegistry($this->cAgent))
                ->servicing($advertise, $server, $this->dTagging->getTags())
            ;

            ($ready = $service->ready())->then(function () use ($service) {
                logger('hrpc')->info('Service has been registered', ['node' => $service->endpoint()]);
            });

            $waits[] = $ready;
        }

        return Promise::all(...$waits);
    }

    /**
     * @return Promised
     */
    public function deregister() : Promised
    {
        $waits = [];

        foreach ($this->sRegistered as $service) {
            ($respond = $service->deregister())->then(static function (Result $result) use ($service) {
                if ($result->success()) {
                    logger('hrpc')->info('Service has been deregister', ['node' => $service->endpoint()]);
                } else {
                    logger('hrpc')->info('Service deregister failed', ['node' => $service->endpoint()]);
                }
            });
            $waits[] = $respond;
        }

        return Promise::all(...$waits);
    }
}
