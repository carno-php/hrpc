<?php
/**
 * Tracing injector
 * User: moyo
 * Date: 2018/9/5
 * Time: 4:50 PM
 */

namespace Carno\HRPC\Components;

use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Container\DI;
use Carno\HRPC\Handlers\ServerWrapper;
use Carno\HRPC\Handlers\TracedIncoming;
use Carno\HRPC\Handlers\TracedOutgoing;
use Carno\RPC\Server;
use Carno\Traced\Contracts\Observer;

class Tracing extends Component implements Bootable
{
    /**
     * @var int
     */
    protected $priority = 51;

    /**
     * @var array
     */
    protected $dependencies = [Observer::class];

    /**
     * @param Application $app
     */
    public function starting(Application $app) : void
    {
        /**
         * @var Observer $observer
         */

        $observer = DI::get(Observer::class);

        $observer->transportable(static function () {
            Server::layers()->has(TracedOutgoing::class)
                || Server::layers()->prepend(ServerWrapper::class, DI::object(TracedOutgoing::class));
            Server::layers()->has(TracedIncoming::class)
                || Server::layers()->append(ServerWrapper::class, DI::object(TracedIncoming::class));
        }, static function () {
            Server::layers()->remove(TracedOutgoing::class);
            Server::layers()->remove(TracedIncoming::class);
        });
    }
}
