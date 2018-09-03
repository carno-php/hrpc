<?php
/**
 * RPC server init
 * User: moyo
 * Date: 18/03/2018
 * Time: 3:20 PM
 */

namespace Carno\HRPC\Components;

use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Container\DI;
use Carno\HRPC\Handlers\ExceptionDump;
use Carno\HRPC\Handlers\RequestLogger;
use Carno\HRPC\Handlers\ServerReplier;
use Carno\HRPC\Handlers\ServerUnavailable;
use Carno\HRPC\Handlers\ServerWrapper;
use Carno\HRPC\Handlers\TrafficMonitor;
use Carno\Monitor\Daemon;
use Carno\RPC\Handlers\ServiceInvoker;
use Carno\RPC\Server;
use Carno\RPC\Service\Dispatcher;

class Serving extends Component implements Bootable
{
    /**
     * @var array
     */
    protected $dependencies = [Dispatcher::class];

    /**
     * @param Application $app
     */
    public function starting(Application $app) : void
    {
        // assign global extensions manager of server
        Server::layers()->append(
            null,
            DI::object(ExceptionDump::class),
            DI::object(ServerReplier::class),
            DI::object(ServerWrapper::class),
            DI::object(RequestLogger::class),
            DI::object(ServiceInvoker::class)
        );

        // initial mark server "unavailable"
        Server::layers()->append(ServerWrapper::class, DI::object(ServerUnavailable::class));

        $app->starting()->add(static function () {
            // add traffic monitor layer
            DI::has(Daemon::class) && Server::layers()->append(
                ServerWrapper::class,
                DI::object(TrafficMonitor::class)
            );
        });

        $app->starting()->done()->then(static function () {
            // mark server "available" when bootstrap done
            Server::layers()->remove(ServerUnavailable::class);
        });
    }
}
