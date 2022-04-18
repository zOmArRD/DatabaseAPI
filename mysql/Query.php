<?php
/*
 * Created by PhpStorm.
 *
 * User: zOmArRD
 * Date: 17/4/2022
 *
 * Copyright © 2022 GhostlyMC Network (omar@ghostlymc.live) - All Rights Reserved.
 */
declare(strict_types=1);

namespace zomarrd\ghostly\database\mysql;

use mysqli;
use pocketmine\scheduler\AsyncTask;
use zomarrd\ghostly\lobby\database\Database;

abstract class Query extends AsyncTask
{
    public string $host, $user, $password, $database;
    public int $port;

    public function __construct()
    {
        $this->host = MySQL['host'];
        $this->user =  MySQL['user'];
        $this->password =  MySQL['pass'];
        $this->database = MySQL['db'];
        $this->port = MySQL['port'];
    }

    final public function onRun(): void
    {
        $query = new mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
        if ($query->connect_error) {
            die(PREFIX . 'Could not connect to the database_backup!');
        }
        $this->query($query);
        $query->close();
    }

    abstract public function query(mysqli $mysqli): void;

    /** @noinspection MethodShouldBeFinalInspection */
    public function onCompletion(): void
    {
        Database::getMysql()->submitAsync($this);
    }
}