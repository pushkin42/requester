<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 26.02.17
 * Time: 16:12
 */

namespace Requester\Abstracts;

use Requester\Collection;

abstract class LoggerAbstract
{
    /**
     * @var null
     */
    protected $monolog = null;

    /**
     * @param Collection $options
     */
    public function __construct(Collection $options)
    {
        $this->monolog = $this->init($options);
    }

    /**
     * @return \Monolog\Logger|null
     */
    public function getLogger()
    {
        return $this->monolog;
    }

    /**
     * @param Collection $options
     * @return \Monolog\Logger
     */
    abstract protected function init(Collection $options);

    /**
     * @param string $level
     * @param $message
     * @param array $context
     */
    public function add($message, array $context = [], $level = 'info')
    {
        $this->monolog->{$level}($message, $context);

        return;
    }
}