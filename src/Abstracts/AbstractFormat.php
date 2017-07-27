<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 18.05.17
 * Time: 13:41
 */

namespace Requester\Abstracts;

use Requester\Interfaces\FormatInterface;

class AbstractFormat implements FormatInterface
{
    /**
     * @param       $name
     * @param array $body
     *
     * @return array
     */
    public static function make( $name, array $body )
    {
        /** @var AbstractResponseFormatter $obj */
        $obj = null;

        list( $type, $method ) = explode( '/', $name );

        if ( !is_null( $obj = static::create( $type ) ) ) {
            return $obj->{$method}( $body );
        }

        return $body;
    }

    public static function getMethodCamelCase( $name = '' )
    {
        if ( empty( $name ) ) {
            return null;
        }

        $str = str_replace( '_', ' ', $name );
        $str = mb_convert_case( $str, MB_CASE_TITLE );
        $str = str_replace( ' ', '', $str );

        //$str = lcfirst( $str );

        return $str;
    }

    public static function create( $type ) { }
}