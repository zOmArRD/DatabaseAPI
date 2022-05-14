<?php /** @noinspection MethodShouldBeFinalInspection */

/*
 * Created by PhpStorm.
 *
 * User: zOmArRD
 * Date: 17/4/2022
 *
 * Copyright © 2022 GhostlyMC Network (omar@ghostlymc.live) - All Rights Reserved.
 */

namespace GhostlyMC\DatabaseAPI\mysql;

use mysqli;
use pocketmine\Server;

class MySQL
{
    /**
     * Executes a query on the MySQL database but with prepared statements.
     *
     * @param string        $query
     * @param callable|null $callable
     * @param string        $types <p>
     *                             A string that contains one or more characters which specify the types
     *                             for the corresponding bind variables:
     *                             <table>
     *                             Type specification chars
     *                             <tr valign="top">
     *                             <td>Character</td>
     *                             <td>Description</td>
     *                             </tr>
     *                             <tr valign="top">
     *                             <td>i</td>
     *                             <td>corresponding variable has type integer</td>
     *                             </tr>
     *                             <tr valign="top">
     *                             <td>d</td>
     *                             <td>corresponding variable has type double</td>
     *                             </tr>
     *                             <tr valign="top">
     *                             <td>s</td>
     *                             <td>corresponding variable has type string</td>
     *                             </tr>
     *                             <tr valign="top">
     *                             <td>b</td>
     *                             <td>corresponding variable is a blob and will be sent in packets</td>
     *                             </tr>
     *                             </table>
     *                             </p>
     * @param               $var1
     * @param mixed         ...$_
     *
     * @return void
     */
    public static function runPreparedStatement(string $query, ?callable $callable, $types, &$var1, &...$_): void
    {
        $mysqli = new mysqli(MySQL['host'], MySQL['user'], MySQL['pass'], MySQL['db'], MySQL['port']);
        if ($mysqli->connect_error) {
            die(PREFIX . 'Could not connect to the database!');
        }

        $statement = $mysqli->prepare($query);
        $statement->bind_param($types, $var1, ...$_);

        $statement->execute();

        $result = $statement->get_result();
        $rows = [];

        if (is_bool($result)) {
            if (is_callable($callable)) {
                $callable();
            }
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

    private static array $callbacks = [];

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
    public static function run(string $query, ?callable $callable = null): void
    {
        $mysqli = new mysqli(MySQL['host'], MySQL['user'], MySQL['pass'], MySQL['db']);
        if ($mysqli->connect_error) {
            die(PREFIX . 'Could not connect to the database!');
        }

        $result = $mysqli->query($query);
        $mysqli->close();

        $rows = [];

        if (is_bool($result)) {
            if (is_callable($callable)) {
                $callable();
            }
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
    public static function runAsync(Query $query, ?callable $callable = null): void
    {
        self::$callbacks[spl_object_hash($query)] = $callable;
        Server::getInstance()->getAsyncPool()->submitTask($query);
    }

    /**
     * Run the callable when the query is finished.
     *
     * @param Query $query
     *
     * @return void
     */
    public static function submitAsync(Query $query): void
    {
        $callable = self::$callbacks[spl_object_hash($query)] ?? null;

        if (is_callable($callable)) {
            if (isset($query['rows'])) {
                $callable($query['rows']);
            } else {
                $callable();
            }
        }
    }
}