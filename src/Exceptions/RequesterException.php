<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 25.02.17
 * Time: 16:53
 */

namespace Requester\Exceptions;

use Exception;

class RequesterException extends Exception
{
    public $options = [];

    public function __construct( $message = "", $code = 200, $options = [], Exception $previous = null )
    {
        if ( !empty( $options ) )
        {
            $this->options = $options;
        }

        $this->code    = $code == 0 ? 400 : $code;
        $this->message = $message;
    }

    public function getOptions( $option )
    {
        return array_key_exists( $option, $this->options ) ? $this->options[ $option ] : null;
    }
}