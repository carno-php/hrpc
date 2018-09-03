<?php
/**
 * Service scanner init
 * User: moyo
 * Date: 2018/9/3
 * Time: 12:21 PM
 */

namespace Carno\HRPC\Components;

use Carno\Console\App;
use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Container\DI;
use Carno\RPC\Service\Router;
use Carno\RPC\Service\Scanner as RScanner;

class Scanner extends Component implements Bootable
{
    /**
     * @param Application|App $app
     */
    public function starting(Application $app) : void
    {
        /**
         * @var Router $router
         * @var RScanner $scanner
         */

        if (defined('CWD') && is_file($rf = CWD . '/registers.php')) {
            DI::set(Router::class, $router = DI::object(Router::class));
            DI::set(RScanner::class, $scanner = DI::object(RScanner::class));

            $scanner->sources(...(array) include $rf)->serving();

            $app->named($router->server());

            $app->starting()->add(static function () use ($scanner) {
                $scanner->analyzing();
            });
        }
    }
}
