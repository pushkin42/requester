<?php
/**
 * Created by PhpStorm.
 * User: viktorthegreat
 * Date: 26.02.17
 * Time: 18:16
 */

namespace Requester\Handler;

use Requester\Abstracts\LoggerAbstract;
use Requester\Collection;
use Requester\Logger\BlackHole;
use Stash\Pool;

class CacheLogHandler extends DefaultHandler
{
    /**
     * @var LoggerAbstract
     */
    protected $log;

    /**
     * @var \Stash\Pool
     */
    protected $cache;

    public function __construct( $config = [] )
    {
        parent::__construct( $config );

        $this->initializeLogger();
        $this->initializeCache();
    }

    /**
     * Инициализация логирующей библиотечки по инфе из конфига.
     *
     * @return $this
     */
    public function initializeLogger()
    {
        $class = $this->config->get( 'log.enabled' ) ? $this->config->get( 'log.driver.class' ) : BlackHole::class;

        $this->log = new $class( $this->config->get( 'log.driver.options', $this->getLogDefaultConfig() ) );

        return $this;
    }

    /**
     * Инициализация кэширующей библиотечки по инфе из конфига.
     * Для логирования ошибок stash передаем объект Monolog.
     *
     * @return $this
     */
    public function initializeCache()
    {
        $class = $this->config->get( 'cache.enabled' ) ? $this->config->get( 'cache.driver.class' ) : \Stash\Driver\BlackHole::class;

        $driver = new $class( $this->config->get( 'cache.driver.options', $this->getCacheDefaultConfig() ) );

        $this->cache = new Pool( $driver );

        //если логгер был инициализирован прежде кэша, значит пропихнем его туда.
        //в противном случае логи все равно выключены.
        if ( $this->log instanceof LoggerAbstract )
        {
            $this->cache->setLogger( $this->log->getLogger() );
        }

        return $this;
    }

    /**
     * Поиск алиаса. В случае удачи, вернет его cache lifetime, если тот был задан.
     * В противном случае кэш живет 1 час.
     *
     * @param $alias
     * @param $aliases
     *
     * @return bool|int
     */
    public function searchAlias( $alias, array $aliases = [] )
    {
        if ( isset( $aliases[ $alias ] ) )
        {
            $ttl = $aliases[ $alias ];
        }
        else if ( array_search( $alias, (array)$aliases ) !== false )
        {
            $ttl = 3600;
        }
        else
        {
            return false;
        }

        return $ttl;
    }

    /**
     * Метод выполняется перед отправкой запроса. Даже немногим раньше.
     */
    public function beforeExecute()
    {
        $this->log->add( 'Отправка запроса... ' . $this->aliasLogString(), [
            'method'  => $this->request->method,
            'payload' => $this->request->payload,
            'hash'    => $this->request->hash,
        ] );
    }

    /**
     * Load data from cache.
     *
     * @return bool
     */
    public function beforeExecuteReturn()
    {
        if ( $this->request->config->has( 'cache.aliases' ) && array_key_exists( $this->request->alias, $this->request->config->get( 'cache.aliases' )->toArray() ) )
        {

            $item = $this->cache->getItem( $this->request->alias );

            if ( !$item->isMiss() )
            {

                return $item->get();
            }
        }

        return false;
    }

    public function afterExecuteReturn( $body )
    {
        if ( $this->request->config->has( 'cache.aliases' ) && array_key_exists( $this->request->alias, $this->request->config->get( 'cache.aliases' )->toArray() ) )
        {

            $hasSubqueryOrLifetime = $this->searchAlias( $this->request->alias, $this->request->config->get( 'cache.aliases' )->toArray() );

            if ( $hasSubqueryOrLifetime )
            {
                $item = $this->cache->getItem( $this->request->alias );

                $this->cache->save( $item->expiresAfter( $hasSubqueryOrLifetime )->set( $body ) );
            }
        }

        return $body;
    }

    /**
     * @return null|string
     */
    protected function aliasLogString()
    {
        return empty( $this->request->alias ) ? null : "Алиас: {$this->request->alias}.";
    }

    /**
     * @return null|string
     */
    protected function hashLogString()
    {
        return empty( $this->request->hash ) ? null : "Hash: {$this->request->hash}.";
    }

    /**
     * обработчик ошибок реквеста
     *
     * @param       $message
     * @param array $context
     */
    public function error( $message, array $context = [] )
    {
        $this->log->add( "Ошибка запроса/cURL/Библиотеки: {$message}", [
            'payload' => $this->request->serialized_payload ?: $this->request->payload,
            'alias'   => $this->request->alias,
            'hash'    => $this->request->hash,
        ],
                         'critical'
        );

        error_log( $message );
    }

    private function getLogDefaultConfig()
    {
        return [
            'channel' => 'no_channel',
            'path'    => $this->app->make( 'path.storage' ) . 'logs',
        ];
    }

    private function getCacheDefaultConfig()
    {
        return [
            'path' => $this->app->make( 'path.storage' ) . 'cache',
        ];
    }
}