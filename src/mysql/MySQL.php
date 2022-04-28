<?php /** @noinspection MethodShouldBeFinalInspection */

/*
 * Created by PhpStorm.
 *
 * User: zOmArRD
 * Date: 17/4/2022
 *
 * Copyright © 2022 GhostlyMC Network (omar@ghostlymc.live) - All Rights Reserved.
 */

namespace ghostlymc\database\mysql;

use mysqli;
use pocketmine\Server;

class MySQL
{
    private static ?MySQL $instance = null;

    /**
     * Executes a query on the MySQL database but with prepared statements.
     *
     * @param string        $query
     * @param array         $params
     * @param callable|null $callable
     *
     * @return void
     * @deprecated NO WORKING YET - DO NOT USE
     */
    public function runPreparedStatement(string $query, array $params = [], ?callable $callable = null): void
    {
        $mysqli = new mysqli(MySQL['host'], MySQL['user'], MySQL['pass'], MySQL['db'], MySQL['port']);
        if ($mysqli->connect_error) {
            die(PREFIX . 'Could not connect to the database!');
        }

        $statement = $mysqli->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bind_param($key, $value);
        }

        $statement->execute();

        $result = $statement->get_result();
        $rows = [];

        if ($result === false) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $statement->close();
        $mysqli->close();

        if (is_callable($callable)) {
            $callable($rows);
        }
    }

    /**
     * Loads the MySQL instance if it doesn't exist
     *
     * @return MySQL Returns the MySQL instance
     */
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
     * @param string        $query
     * @param callable|null $callable
     *
     * @return void
     */
    public function run(string $query, ?callable $callable = null): void
    {
        $mysqli = new mysqli(MySQL['host'], MySQL['user'], MySQL['pass'], MySQL['db'], MySQL['port']);
        if ($mysqli->connect_error) {
            die(PREFIX . 'Could not connect to the database!');
        }

        $result = $mysqli->query($query);
        $mysqli->close();

        $rows = [];

        if (is_bool($result)) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        if (is_callable($callable)) {
            $callable($rows);
        }
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