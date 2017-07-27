<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 26.02.17
 * Time: 19:47
 */

namespace Requester\Logger;

use Requester\Abstracts\LoggerAbstract;
use Requester\Collection;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Develop extends LoggerAbstract
{
    /**
     * @param Collection $options
     *
     * @return mixed
     */
    protected function init( Collection $options )
    {
        // the default date format is "Y-m-d H:i:s"
        $dateFormat = "d.m.Y H:i:s";

        // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        $output = str_repeat( '-', 80 )
            . PHP_EOL . "%datetime% | %channel%.%level_name% "
            . PHP_EOL . "%message% "
            . PHP_EOL . "%context% %extra%"
            . PHP_EOL . str_repeat( '-', 80 )
            . PHP_EOL . PHP_EOL;

        $formatter = new LineFormatter( $output, $dateFormat, false, true );

        $stream = new StreamHandler( "{$options['path']}/request.log" );
        $stream->setFormatter( $formatter );

        // create a log channel
        $log = new Logger( $options['channel'] );
        $log->pushHandler( $stream );

        return $log;
    }
}