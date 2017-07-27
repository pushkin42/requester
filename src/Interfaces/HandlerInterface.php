<?php
/**
 * Developer: Roquie
 * DateTime: 21.03.15 19:54
 * Current file name: HandlerInterface.php
 *
 * All rights reserved (c)
 */
namespace Requester\Interfaces;

use Requester\Request;

interface HandlerInterface
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function initialize(Request $request);

    /**
     * Метод выполняется перед отправкой запроса. Даже немногим раньше.
     *
     * @return
     */
    public function beforeExecute();

    /**
     * Load data from cache
     *
     * @return bool
     */
    public function beforeExecuteReturn();

    /**
     * Событие срабатывает после выполнения запроса и получения обработанных данных методом parse;
     *
     * @param         $body
     * @return $this
     */
    public function afterExecute($body);

    /**
     *
     * @param         $body
     * @return $this
     */
    public function afterExecuteReturn($body);

    /**
     * обработчик ошибок реквеста
     *
     * @param         $message
     * @param         $context
     * @return
     */
    public function error($message, array $context = []);

    /**
     * Парсит API Response и возвращает нормальные данные
     *
     * @param string  $body
     * @return mixed
     */
    public function parse($body);

    /**
     * Сериализует body (payload) в строку для запроса.
     * Все что передали в метод body, попадет сюда.
     *
     * @param mixed   $payload
     * @return string
     */
    public function serialize($payload);
}