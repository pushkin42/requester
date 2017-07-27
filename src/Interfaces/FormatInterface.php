<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 26.02.17
 * Time: 15:45
 */

namespace Requester\Interfaces;

interface FormatInterface
{
    /**
     * @param       $name
     * @param array $body
     *
     * @return mixed
     *
     */
    public static function make( $name, array $body );

    /**
     * @param string $name
     *
     * @return mixed
     */
    public static function getMethodCamelCase( $name = '' );

    /**
     * @param $type
     *
     * @return mixed
     */
    public static function create( $type );
}