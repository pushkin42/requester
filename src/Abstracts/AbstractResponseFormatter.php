<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 25.02.17
 * Time: 23:27
 */

namespace Requester\Abstracts;

use Requester\Interfaces\FormatterInterface;
use Requester\HelperFormatter;

abstract class AbstractResponseFormatter implements FormatterInterface
{
    /**
     * @var HelperFormatter
     */
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperFormatter();
    }

    /**
     * convert date to readable for the humans format
     *
     * @param        $string
     * @param string $format
     *
     * @return mixed
     */
    protected function date( $string, $format = '' )
    {
        return $this->helper->formatDate($string, $format);
    }

    /**
     * Склонение существительных
     *
     * @param $num
     * @param $one
     * @param $two
     * @param $five
     *
     * @return mixed
     */
    protected function getNoun($num, $one, $two, $five)
    {
        return $this->helper->getNoun($num, $one, $two, $five);
    }

    public function translate()
    {

    }

}