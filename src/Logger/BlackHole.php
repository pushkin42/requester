<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 26.02.17
 * Time: 18:19
 */

namespace Requester\Logger;

use Requester\Abstracts\LoggerAbstract;
use Requester\Collection;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

class BlackHole extends LoggerAbstract
{
    /**
     * @param Collection $options
     * @return mixed
     */
    protected function init(Collection $options)
    {
        //@todo: set options and channel to null;
        $log = new Logger($options->get('channel', 'blackHoleChanel'));

        return $log->pushHandler(new NullHandler());
    }

    /**
     * @param        $message
     * @param array  $context
     * @param string $level
     */
    public function add($message, array $context = [], $level = 'info')
    {
        // nothing :)
    }
}