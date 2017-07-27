<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 24.02.17
 * Time: 22:38
 */

namespace Requester\Abstracts;

use Requester\Request;
use Requester\Interfaces\HandlerInterface;
use Requester\Collection;
use DOMDocument;
use ErrorException;

abstract class AbstractDefaultHandler implements HandlerInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Collection
     */
    protected $config;

    /**
     * AbstractDefaultHandler constructor.
     *
     * @param array $config
     */
    public function __construct( $config = [] )
    {
        $this->config = Collection::make( $config );

        if ( !$this->config ) {
            throw new \RuntimeException( 'Configuration not initialized. Please use Config class and load settings.' );
        }
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function initialize( Request $request )
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Метод выполняется перед отправкой запроса. Даже немногим раньше.
     */
    public function beforeExecute()
    {
        // TODO: Implement beforeExecute() method.
    }

    /**
     * Load data from cache
     */
    public function beforeExecuteReturn()
    {
        // TODO: Implement beforeExecuteReturn() method.
    }

    /**
     * Событие срабатывает после выполнения запроса и получения обработанных данных методом parse;
     *
     * @param $body
     *
     * @return $this|void
     */
    public function afterExecute( $body )
    {
        // TODO: Implement afterExecute() method.
    }


    /**
     *
     * @param $body
     *
     * @return $this
     */
    public function afterExecuteReturn( $body )
    {
        // TODO: Implement afterExecuteReturn() method.
    }

    /**
     * обработчик ошибок реквеста
     *
     * @param       $message
     * @param array $context
     *
     * @throws ErrorException
     */
    public function error( $message, array $context = [] )
    {
        error_log( $message );

        throw new ErrorException( 'При выполнении запроса "' . $this->request->alias . '" произошла ошибка', 400, $context[ 'exception' ] );
    }

    /**
     * Парсит API Response и возвращает нормальные данные
     *
     * @param string $body
     *
     * @return mixed
     */
    public function parse( $body )
    {
        return $body;
    }

    /**
     * Сериализует body (payload) в строку для запроса.
     * Все что передали в метод body, попадет сюда.
     *
     * @param mixed $payload
     *
     * @return string
     */
    public function serialize( $payload )
    {
        return $payload;
    }

    /**
     * Корректная проверка на валидность XML. Только, мать его, XML, без HTML.
     * Ошибок в случае некоректных входных данных не выдает.
     *
     * Даже сюда выложил: http://stackoverflow.com/a/31240779/2355900
     *
     * @param $content
     *
     * @return bool
     */
    protected function isValidXml( $content )
    {
        $content = trim( $content );
        if ( empty( $content ) ) {
            return false;
        }
        //html в жопу.
        if ( stripos( $content, '<!DOCTYPE html>' ) !== false ) {
            return false;
        }

        libxml_use_internal_errors( true );
        simplexml_load_string( $content );
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return empty( $errors );
    }

    protected function isValidJson( $content )
    {
        json_decode( $content );

        return ( json_last_error() === JSON_ERROR_NONE );
    }

    /**
     * convert XML response to array
     *
     * @param $string
     *
     * @return mixed
     */
    public function xmlToArray( $string )
    {

        $doc = new DOMDocument();
        $doc->loadXML( $string );
        $root = $doc->documentElement;

        return $this->DomNodeToArray( $root );
    }

    private function DomNodeToArray( $node )
    {
        $output = [];

        switch ( $node->nodeType ) {
            case XML_CDATA_SECTION_NODE :
            case XML_TEXT_NODE :

                $output = trim( $node->textContent );

                break;

            case XML_ELEMENT_NODE :

                for ( $i = 0, $m = $node->childNodes->length; $i < $m; $i++ ) {
                    $child = $node->childNodes->item( $i );
                    $v = $this->DomNodeToArray( $child );

                    if ( isset( $child->tagName ) ) {
                        $t = $child->tagName;

                        if ( !array_key_exists( $t, $output ) ) {
                            $output[ $t ] = [];
                        }

                        $output[ $t ][] = $v;
                    } elseif ( $v || $v === '0' ) {
                        $output = (string)$v;
                    }
                }

                //Has attributes but isn't an array
                if ( $node->attributes->length && !is_array( $output ) ) {
                    $output = [ '@content' => $output ]; //Change output into an array.
                }

                if ( is_array( $output ) ) {
                    if ( $node->attributes->length ) {
                        $a = [];
                        foreach ( $node->attributes as $attrName => $attrNode ) {
                            $a[ $attrName ] = (string)$attrNode->value;
                        }

                        $output[ 'attr' ] = $a;
                    }
                    foreach ( $output as $t => $v ) {
                        if ( is_array( $v ) && count( $v ) == 1 && $t != '@attributes' ) {
                            $output[ $t ] = $v[ 0 ];
                        }
                    }
                }
                break;
        }

        return $output;
    }

    /**
     * convert JSON response to array
     *
     * @param $string
     *
     * @return mixed
     */
    public function jsonToArray( $string )
    {
        return json_decode( $string, true );
    }

    public function arrayToJson( array $array )
    {
        return json_encode( $array );
    }

    /**
     * Получение данных для отправки запроса
     *
     * Array =>
     * [
     *    'auth' => [
     *        'login' => 'q',
     *        'pwd' => 'q'
     *    ]
     * ]
     *
     * @return bool
     */
    public function getXml()
    {
        $arguments = func_get_args();

        $data = array_shift( $arguments );
        $auth = false;

        if ( array_key_exists( 'auth', $data ) ) {
            $auth = $data[ 'auth' ];
            unset( $data[ 'auth' ] );
        }

        if ( $auth ) {
            $data[ 'auth' ] = $this->xmlAuth();
        }

        $xml_header = '<?xml version="1.0" standalone="yes"?>';

        $xml_root = 'root';

        $request_body = $xml_header;
        $request_body .= "<" . $xml_root . ">\n";

        foreach ( $data as $type => $params ) {
            $request_body .= '<' . $type . ' ' . $this->arrayToParams( $params ) . " />\n";
        }

        $request_body .= '</' . $xml_root . '>';

        return $request_body;
    }

    /**
     * Добавление авторизации в xml
     *
     * @return array
     */
    private function xmlAuth()
    {
        return [
            'login'    => $this->request->config[ 'auth.login' ],
            'password' => $this->request->config[ 'auth.password' ],
        ];
    }

    /**
     * Формирование параметров xml
     *
     * @param array $data
     *
     * @return bool|string
     */
    private function arrayToParams( array $data )
    {
        $result = [];

        foreach ( $data as $key => $value ) {
            $result [] = $key . ' = "' . $value . '"';
        }

        return ( !empty( $result ) ) ? implode( " ", $result ) : '';
    }

    /**
     * Remove null byte from body
     *
     * @param $body
     *
     * @return string
     */
    public function stripBom( $body )
    {
        if ( substr( $body, 0, 3 ) === "\xef\xbb\xbf" )  // UTF-8
        {
            $body = substr( $body, 3 );
        } else {
            if ( substr( $body, 0, 4 ) === "\xff\xfe\x00\x00" || substr( $body, 0, 4 ) === "\x00\x00\xfe\xff" )  // UTF-32
            {
                $body = substr( $body, 4 );
            } else {
                if ( substr( $body, 0, 2 ) === "\xff\xfe" || substr( $body, 0, 2 ) === "\xfe\xff" )  // UTF-16
                {
                    $body = substr( $body, 2 );
                }
            }
        }

        return $body;
    }
}