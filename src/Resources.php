<?php
/**
 * Resources defined
 * User: moyo
 * Date: 2018/8/31
 * Time: 2:49 PM
 */

namespace Carno\HRPC;

use Carno\HRPC\Commands\ServerStart;
use Carno\HRPC\Components\Scanner;

interface Resources
{
    public const APPS = [
        ServerStart::class,
    ];

    public const COMPONENTS = [
        Scanner::class,
    ];
}
