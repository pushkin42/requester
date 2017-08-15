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

    public function getAdj( $num, $one, $two )
    {
        $num %= 100;

        if($num == 11) return $two;

        $num %= 10;

        if($num == 1) return $one;

        return $two;
    }

    /**
     * Форматирование телефонного номера (убираем все спец символы и оставляем только цифры)
     *
     * @param string $string
     *
     * @return bool|string
     * @internal param array|string $format
     */
    public static function clearAllSymbols( $string )
    {
        $string = preg_replace( '/[^0-9]/', '', $string );

        return $string;
    }

    /**
     * Форматирование телефонного номера (убираем все спец символы и оставляем только цифры)
     *
     * @param string $phone
     *
     * @return bool|string
     * @internal param array|string $format
     */
    public static function phone_format( $phone )
    {
        $phone = preg_replace( '/[^0-9]/', '', $phone );

        if ( !empty($phone) && $phone[ 0 ]=='8' ) {
            $phone[ 0 ] = '7';
        }

        return $phone;
    }
}