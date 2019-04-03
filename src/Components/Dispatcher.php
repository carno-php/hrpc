<?php
/**
 * Service dispatcher init
 * User: moyo
 * Date: 13/12/2017
 * Time: 10:48 AM
 */

namespace Carno\HRPC\Components;

use Carno\Console\App;
use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Container\DI;
use Carno\Promise\Promise;
use Carno\RPC\Service\Dispatcher as RDispatcher;
use Carno\RPC\Service\Scanner;
use Throwable;

class Dispatcher extends Component implements Bootable
{
    /**
     * @var array
     */
    protected $dependencies = [Scanner::class];

    /**
     * @param Application|App $app
     */
    public function starting(Application $app) : void
    {
        /**
         * @var RDispatcher $dispatcher
         */

        $dispatcher = DI::set(RDispatcher::class, DI::object(RDispatcher::class));

        $started = $app->starting()->done();
        $shutdown = Promise::deferred();

        $app->starting()->add(static function () use ($dispatcher, $started, $shutdown) {
            $dispatcher->preparing($started, $shutdown)->catch(function (Throwable $e) {
                logger('hrpc')->error(
                    'Service startup failed when initialize',
                    ['ec' => get_class($e), 'em' => $e->getMessage()]
                );
            });
        });

        $app->stopping()->add(static function () use ($shutdown) {
            $shutdown->resolve();
        });
    }
}
