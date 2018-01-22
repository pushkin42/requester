<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 24.02.17
 * Time: 22:10
 */

namespace Requester;

use GuzzleHttp\Psr7\Response;
use Requester\Handler\DefaultHandler;
use Requester\Interfaces\HandlerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

class Request
{
    /**
     * Тип обработчика. Это класс имплементированный от HandlerInterface и
     * с написанной реализацией сериализации body (payload) в строку запроса, и
     * парсера ответа от API (response).
     * Также поддерживает события.
     *
     * @var HandlerInterface
     */
    protected $handler;

    public $uri;
    public $payload;
    public $serialized_payload = null;
    public $method             = Http::GET;
    public $headers            = [];
    public $options            = [];
    public $auth               = [];
    public $content_type; // mime

    const OPENSSL_ENCRYPT_CMD = 'openssl smime -sign -signer "%s" -inkey "%s" -nochain -nocerts -outform PEM -nodetach -passin pass:%s';

    /**
     * Алиас для того, чтобы кэшировать запросы. Запросы без алиаса не кэшируются.
     *
     * @var string
     */
    public $alias = '';

    /**
     * Конфигурация либы.
     *
     * @var Collection
     */
    public $config;

    /**
     * Хэш заголовков, урла и сериализованного запроса (body)
     *
     * @var
     */
    public $hash = '';

    public function __construct()
    {
        $this->setHandler( DefaultHandler::class );
    }

    /**
     * Установка конфигураций
     *
     * @param $data
     *
     * @return $this
     */
    public function setConfig( $data )
    {
        $this->config = Collection::make( $data );

        return $this;
    }

    /**
     * URL или URI адреса для отправки запроса
     *
     * @param $url
     *
     * @return $this
     */
    public function setEndpoint( $url )
    {
        $this->uri = $url;

        return $this;
    }

    /**
     * Add an additional header to the request
     * Can also use the cleaner syntax of
     *
     * @return $this
     */
    public function setHeader()
    {
        $args = func_get_args();

        if ( is_array( $args[ 0 ] ) )
        {
            foreach ( $args[ 0 ] as $key => $value )
                $this->headers[ 'headers' ][ $key ] = $value;
        }
        else
        {
            $this->headers[ 'headers' ][ $args[ 0 ] ] = $args[ 1 ];
        }

        return $this;
    }

    /**
     * Set option cURL request (timeout, ssl_verify, etc..)
     *
     * @return $this
     */
    public function setOption()
    {
        $args = func_get_args();

        $options = [];

        if ( is_array( $args[ 0 ] ) )
        {
            foreach ( $args[ 0 ] as $key => $value )
                $options[ $key ] = $value;
        }
        else
        {
            $options[ $args[ 0 ] ] = $args[ 1 ];
        }

        $this->options += $options;

        return $this;
    }

    /**
     * Set the method.  Shouldn't be called often as the preferred syntax
     * for instantiation is the method specific factory methods.
     *
     * @return Request this
     *
     * @param string $method
     */
    public function setMethod( $method )
    {
        if ( empty( $method ) )
        {
            return $this;
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Базовая авторизация на ресурсе
     *
     * @return $this
     */
    public function basicAuth()
    {
        $auth = [ 'auth' => [ $this->config->get( 'auth.basic.username' ), $this->config->get( 'auth.basic.password' ) ] ];

        $this->options += $auth;

        return $this;
    }

    /**
     * Прикрепление сертификата
     *
     * @param $path
     * @param $password
     *
     * @return $this
     */
    public function cert( $path, $password = null )
    {
        $options = [ 'cert' => [ $path, $password ] ];

        $this->options += $options;

        return $this;
    }

    /**
     * Прикрепление ключа к сертификату
     *
     * @param $path
     *
     * @return $this
     */
    public function ssl_key( $path )
    {
        $options = [ 'ssl_key' => $path ];

        $this->options += $options;

        return $this;
    }

    /**
     * Set the body of the request
     *
     *
     * @param mixed $payload
     *
     * @return Request this
     */
    public function body( $payload )
    {
        if ( empty( $payload ) )
        {
            return $this;
        }
        $this->payload = $payload;

        return $this;
    }

    /**
     * Установка обработчика запросов
     *
     * @param $handler
     *
     * @return $this
     */
    public function setHandler( $handler )
    {
        $handler = new $handler( $this->config );

        if ( !$handler instanceof HandlerInterface )
        {
            throw new \RuntimeException( 'Handler not initialized. Please use Handler class instanceof HandlerInterface.' );
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * Helper function to set the Content-Type and Expected as same in
     * one swoop
     *
     * @return Request this
     *
     * @param string $mime mime type to use for content type and expected return type
     */
    public function setMime( $mime )
    {
        if ( empty( $mime ) )
        {
            return $this;
        }

        $this->setContentType(
            Mime::getFullMime( $mime )
        );

        return $this;
    }

    /**
     * Тип передаваемых данных
     *
     * @param $contentType
     *
     * @return $this
     */
    public function setContentType( $contentType )
    {
        $this->content_type = $contentType;

        $this->headers[ 'headers' ][ 'Content-type' ] = $contentType;

        return $this;
    }

    /**
     * Установка названия запроса (необходимо для идентификации запроса и для кэширования)
     *
     * @param $name
     *
     * @return $this
     */
    public function setAlias( $name )
    {
        if ( empty( $name ) )
        {
            return $this;
        }
        $this->alias = $name;

        return $this;
    }

    /**
     * Вызов методов обработчика запросов
     *
     * @return mixed
     */
    public function handle()
    {
        $arguments    = func_get_args();
        $handleMethod = array_shift( $arguments );

        return call_user_func_array( [ $this->handler->initialize( $this ), $handleMethod ], $arguments );
    }

    /**
     * Actually send off the request, and parse the response
     * @return array|string of parsed results
     */
    public function send()
    {
        $this->hash();

        if ( $data = $this->handle( 'beforeExecuteReturn' ) )
        {
            return $data;
        }

        if ( !empty( $this->payload ) )
        {
            $this->serialized_payload = $this->handle( 'serialize', $this->payload );
        }

        $this->uri = $this->formatEndpoint();

        $this->handle( 'beforeExecute' );

        $exception = false;
        $res       = null;

        try
        {
            $client = new Client();

            $res = $client->request(
                $this->method,
                $this->uri,
                $this->headers + $this->formatBody() + $this->options
            );
        }
        catch ( Exception $e )
        {
            $exception = $e;
        }

        if ( is_bool( $exception ) && $res instanceof Response )
        {
            $body = $this->handle( 'parse', $res->getBody() );

            if ( $data = $this->handle( 'afterExecuteReturn', $body ) )
            {
                return $data;
            }

            $this->handle( 'afterExecute', $body );

            return $body;
        }

        $msg = null;

        if($exception instanceof Exception)
        {
            $msg = $exception->getMessage();

            if($exception instanceof RequestException)
            {
                $msg = $exception->getRequest();

                if ( $exception->hasResponse() ) {
                    $msg = $exception->getResponse()->getBody()->getContents();
                }
            }
        }

        return $this->handle( 'error', $msg, [ 'exception' => $exception instanceof Exception ? $exception : null ] );
    }

    /**
     * Данные с соответствуюшим ключом, исходя из типа данных, формы данных и метода передачи данных
     *
     * @return array
     */
    private function formatBody()
    {
        $body = [];

        if ( $this->content_type == Mime::JSON )
        {
            $body[ 'json' ] = $this->serialized_payload;
        }
        elseif ( $this->method == Http::GET )
        {
            $body[ 'query' ] = $this->serialized_payload;
        }
        elseif ( is_array( $this->serialized_payload ) )
        {
            $body[ 'form_params' ] = $this->serialized_payload;
        }
        else
        {
            $body[ 'body' ] = $this->serialized_payload;
        }

        return $body;
    }

    /**
     * Установка URL (если не задали URL/URI через setEndpoint)
     *
     * @return mixed|string
     */
    private function formatEndpoint()
    {
        $url = '';

        if ( empty( $this->uri ) )
        {
            $url = $this->config[ 'url' ];
        }
        elseif ( str_contains( $this->uri, [ 'http', 'https' ] ) )
        {
            $url = $this->uri;
        }
        elseif ( $this->config->has( 'url' ) )
        {
            $url = $this->config[ 'url' ] . $this->uri;
        }

        if ( empty( $url ) )
        {
            throw new RuntimeException( 'Attempting to send a request before defining a URI endpoint.' );
        }

        return $url;
    }

    /**
     * Хэш ссылки, заголовков и обработанного body
     * @todo: вынести алиасы и хэш запроса в стандартный обработчик
     *
     * @return $this
     */
    private function hash()
    {
        $this->hash = md5( $this->uri . serialize( $this->headers ) . serialize( $this->payload ) );

        return $this;
    }
}