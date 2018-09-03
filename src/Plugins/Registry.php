<?php
/**
 * Service registry
 * User: moyo
 * Date: 27/12/2017
 * Time: 6:02 PM
 */

namespace Carno\HRPC\Plugins;

use Carno\Console\Boot\Waited;
use Carno\Container\DI;
use Carno\HRPC\Serviced\Register;
use Carno\Net\Address;
use Carno\Net\Contracts\Conn;
use Carno\Net\Events;
use Carno\Process\Piping;
use Carno\Process\Program;
use Carno\Promise\Promised;
use Carno\Serving\Contracts\Plugins;

class Registry extends Program implements Plugins
{
    /**
     * @var string
     */
    protected $name = 'service.registry';

    /**
     * @var Register
     */
    private $register = null;

    /**
     * @var Address
     */
    private $advertise = null;

    /**
     * @var int
     */
    private $waiting = 1;

    /**
     * @var int
     */
    private $ready = 0;

    /**
     * @var int
     */
    private $waits = null;

    /**
     * ServiceRegistry constructor.
     * @param Address $advertise
     * @param int $waits
     */
    public function __construct(Address $advertise, int $waits = 1)
    {
        $this->advertise = $advertise;
        $this->waits = $waits;
    }

    /**
     * @return bool
     */
    public function enabled() : bool
    {
        return DI::has(Register::class) ? !! $this->register = DI::get(Register::class) : false;
    }

    /**
     * @param Events $events
     */
    public function hooking(Events $events) : void
    {
        $forked = $this->fork();

        $forked->waiting($this->waits);

        $events
        ->attach(Events\Server::CREATED, static function (Conn $serv) use ($forked) {
            $forked->startup($serv->server()->raw());
        })
        ->attach(Events\Server::STARTUP, static function (Conn $serv) use ($forked) {
            $forked->porting($serv->local()->port());
        })
        ->attach(Events\Worker::STARTED, static function (Conn $serv) use ($forked) {
            $startup = $serv->ctx()->get('WG:STARTING');
            if ($startup instanceof Waited) {
                $startup->done()->then(function () use ($serv, $forked) {
                    $forked->ready($serv->worker());
                });
            } else {
                trigger_error('No WG:STARTING in serv context', E_USER_WARNING);
            }
        })
        ->attach(Events\Worker::STOPPED, static function () use ($forked) {
            $forked->shutdown();
        });
    }

    /**
     * @param int $listen
     */
    public function porting(int $listen) : void
    {
        if ($this->advertise->port() !== $listen) {
            $this->advertise = new Address($this->advertise->host(), $listen);
        }
    }

    /**
     * @param int $all
     */
    public function waiting(int $all) : void
    {
        $this->waiting = $all;
    }

    /**
     * @param int $wid
     */
    public function ready(int $wid) : void
    {
        $this->ready ++;
        $this->starting();
    }

    /**
     * @param Piping $piping
     */
    protected function forking(Piping $piping) : void
    {
        // do nothing
    }

    /**
     * server register
     */
    protected function starting() : void
    {
        if ($this->ready === $this->waiting) {
            $this->register->serviceRegister($this->advertise);
        }
    }

    /**
     * service deregister
     * @param Promised $wait
     */
    protected function stopping(Promised $wait) : void
    {
        $this->register->serviceDeregister()->sync($wait);
    }
}
