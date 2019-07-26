<?php
/**
 * RPC server instance
 * User: moyo
 * Date: 2018/5/25
 * Time: 2:31 PM
 */

namespace Carno\HRPC;

use Carno\HTTP\Server as HServer;
use Carno\Net\Address;
use Carno\Net\Contracts\Conn;
use Carno\Net\Events\HTTP;
use Carno\Net\Events\Worker;
use Carno\RPC\Server as RServer;
use Carno\Serving\Chips\Boots;
use Carno\Serving\Chips\Events;
use Carno\Serving\Chips\Plugins;
use Carno\Serving\Chips\Wants;

class Server
{
    use Events;
    use Boots;
    use Wants;
    use Plugins;

    /**
     * @var string
     */
    private $name = null;

    /**
     * @var Address
     */
    private $listen = null;

    /**
     * HRPC constructor.
     * @param string $name
     * @param Address $listen
     */
    public function __construct(string $name, Address $listen)
    {
        $this->name = $name;
        $this->listen = $listen;

        $this->events()->attach(Worker::STARTED, static function (Conn $ctx) {
            $ctx->events()->attach(HTTP::REQUESTING, RServer::layers()->handler());
        });
    }

    /**
     * @param int $workers
     */
    public function run(int $workers) : void
    {
        HServer::listen($this->listen, $this->events(), $workers, $this->name)->serve();
    }
}
