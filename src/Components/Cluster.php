<?php
/**
 * Cluster discovery init
 * User: moyo
 * Date: 13/12/2017
 * Time: 12:00 PM
 */

namespace Carno\HRPC\Components;

use Carno\Cluster\Discover\Adaptors\Consul;
use Carno\Cluster\Discover\Discovered;
use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Container\DI;
use Carno\HRPC\Serviced\Register;
use Carno\RPC\Service\Router;

class Cluster extends Component implements Bootable
{
    /**
     * @var array
     */
    protected $dependencies = [Discovered::class, Router::class];

    /**
     * @param Application $app
     */
    public function starting(Application $app) : void
    {
        if (DI::get(Discovered::class) instanceof Consul) {
            DI::set(Register::class, DI::object(Register::class));
        }
    }
}
