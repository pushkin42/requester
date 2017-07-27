<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 25.02.17
 * Time: 23:58
 */

namespace Requester;

use DateTime;
use IntlDateFormatter;

class HelperFormatter
{
    public function formatDate($date, $pattern = '')
    {
        if( ! $date instanceof DateTime)
        {
            $date = new DateTime($date);
        }

        $intl = ( new IntlDateFormatter('ru_RU', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Europe/Moscow') );

        $intl->setPattern($pattern);

        return $intl->format($date);
    }

    public function getNoun( $num, $one, $two, $five )
    {
        $num = abs( $num );

        $num = $num % 100;
        if ( $num >= 5 && $num <= 20 )
        {
            return $five;
        }

        $num = $num % 10;
        if ( $num == 1 )
        {
            return $one;
        }
        if ( $num >= 2 && $num <= 4 )
        {
            return $two;
        }

        return $five;
    }
}