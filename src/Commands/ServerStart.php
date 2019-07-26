<?php
/**
 * RPC server -> start
 * User: moyo
 * Date: 12/12/2017
 * Time: 12:45 PM
 */

namespace Carno\HRPC\Commands;

use Carno\Console\Based;
use Carno\Console\Configure;
use Carno\Console\Contracts\Application;
use Carno\HRPC\Components\Dispatcher;
use Carno\HRPC\Components\Register;
use Carno\HRPC\Components\Serving;
use Carno\HRPC\Components\Tracing;
use Carno\HRPC\Plugins\Registry;
use Carno\HRPC\Server;
use Carno\Net\Address;
use Carno\Serving\Chips\HWIGet;
use Carno\Serving\Contracts\Options;
use Carno\Serving\Options as Opt;
use Carno\Serving\Plugins\LiveReloading;
use Carno\Serving\Plugins\MetricsExporter;
use Carno\Serving\Plugins\ServerMonitor;
use Symfony\Component\Console\Input\InputOption;

class ServerStart extends Based
{
    use HWIGet;
    use Opt\Common;
    use Opt\Metrics;
    use Opt\Discovery;
    use Opt\Listener;

    // service broadcast ip
    private const OPT_ADVERTISE = 'broadcast-ip';

    /**
     * @var string
     */
    protected $name = 'server:start';

    /**
     * @var string
     */
    protected $description = 'Start the RPC server';

    /**
     * @var array
     */
    protected $components = [
        Dispatcher::class,
        Serving::class,
        Register::class,
        Tracing::class,
    ];

    /**
     * @var bool
     */
    protected $ready = false;

    /**
     * @param Configure $conf
     */
    protected function options(Configure $conf) : void
    {
        $conf->addOption(self::OPT_ADVERTISE, null, InputOption::VALUE_OPTIONAL, 'Advertised IP', '127.0.0.1');
    }

    /**
     * @param Application $app
     */
    protected function firing(Application $app) : void
    {
        $workers = $app->input()->getOption(Options::WORKERS) ?: $this->numCPUs();

        (new Server(
            $app->name(),
            new Address($app->input()->getOption(Options::LISTEN))
        ))
            ->bootstrap($this->bootstrap())
            ->plugins(
                new Registry(new Address($app->input()->getOption(self::OPT_ADVERTISE)), $workers),
                new LiveReloading(),
                new ServerMonitor(),
                new MetricsExporter()
            )
            ->wants($app->starting(), $app->stopping())
            ->run($workers)
        ;
    }
}
