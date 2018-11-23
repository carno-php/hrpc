<?php
/**
 * Service registry init
 * User: moyo
 * Date: 13/12/2017
 * Time: 12:00 PM
 */

namespace Carno\HRPC\Components;

use Carno\Cluster\Discovery\Discovered;
use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Consul\Types\Agent;
use Carno\Container\DI;
use Carno\HRPC\Serviced\ConsulRG;
use Carno\HRPC\Serviced\Registry;
use Carno\RPC\Service\Router;

class Register extends Component implements Bootable
{
    /**
     * @var array
     */
    protected $prerequisites = [Discovered::class];

    /**
     * @var array
     */
    protected $dependencies = [Router::class, Agent::class];

    /**
     * @param Application $app
     */
    public function starting(Application $app) : void
    {
        DI::set(Registry::class, DI::object(ConsulRG::class));
    }
}
