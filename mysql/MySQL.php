<?php /** @noinspection MethodShouldBeFinalInspection */

/*
 * Created by PhpStorm.
 *
 * User: zOmArRD
 * Date: 17/4/2022
 *
 * Copyright © 2022 GhostlyMC Network (omar@ghostlymc.live) - All Rights Reserved.
 */

namespace zomarrd\ghostly\database\mysql;

use mysqli;
use pocketmine\Server;

class MySQL
{
    private static MySQL $instance;

    public static function getInstance(): MySQL
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private array $callbacks = [];

    /**
     * Executes a query on the MySQL database.
     *
     * NOT ASYNC.
     *
     * The database data must be provided by the user.
     *
     * @param string $query
     *
     * @return void
     */
    public function run(string $query): void
    {
        $mysqli = new mysqli(MySQL['host'], MySQL['user'], MySQL['pass'], MySQL['db'], MySQL['port']);
        if ($mysqli->connect_error) {
            die(PREFIX . 'Could not connect to the database!');
        }
        $mysqli->query($query);
        $mysqli->close();
    }

    /**
     * Execute a query on the MySQL database asynchronously.
     * for better performance.
     *
     * @param Query         $query
     * @param callable|null $callable
     *
     * @return void
     */
    public function runAsync(Query $query, ?callable $callable = null): void
    {
        $this->callbacks[spl_object_hash($query)] = $callable;
        Server::getInstance()->getAsyncPool()->submitTask($query);
    }

    public function submitAsync(Query $query): void
    {
        $callable = $this->callbacks[spl_object_hash($query)] ?? null;

        if (is_callable($callable)) {
            $callable($query['rows']);
        }
    }
}