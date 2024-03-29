# DatabaseAPI

Project Views: ![visitors](https://visitor-badge.glitch.me/badge?page_id=DatabaseAPI)

A simple API to make queries asynchronously or not to MySQL

## Usage example

SelectQuery class

```php
class SelectQuery extends \zomarrd\ghostly\database\mysql\Query
{

    public mixed $rows;
  
    public function __construct(private string $query) { parent::__construct(); }
  
    final public function query(mysqli $mysqli): void
    {
        $result = $mysqli->query($this->query);
        $rows = [];

        if ($result === false) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->rows = serialize($rows);
    }

    final public function onCompletion(): void
    {
        if ($this->rows === null) {
            return;
        }

        $this->rows = unserialize($this->rows, [[]]);
        parent::onCompletion();
    }
}
```

Usage of SelectQuery on AsyncQuery

```php
public function loadServers(): void {
    MySQL::getInstance()->runAsync(new SelectQuery("SELECT * FROM servers"), function($rows): void {
        foreach ($rows as $row) {
            //Example function
            $server = new Server($row['name'], $row['ip'], (int)$row['port']);
          
            Ghostly::$logger->info(sprintf("%sThe server (%s) has been loaded from the database!", PREFIX, $server->getName()));
        }   
    });
}
```

**NOTE**: The callback function must be defined in second parameter of runAsync function.

---

## Another Usage example

InsertQuery class

```php
final class InsertQuery extends Query
{

    public function __construct(private string $query) { parent::__construct(); }

    public function query(mysqli $mysqli): void
    {
        $mysqli->query($this->getQuery());
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}

MySQL::getInstance()->runAsync(new InsertQuery("INSERT INTO servers (name, ip, port) ' VALUES ('GhostlyMC', 'ghostlymc.live', '19132')"), function(): void {
    Ghostly::$logger->info(sprintf("%sThe server has been inserted into the database!", PREFIX));
});
```

## WARNING

This project is under development and is not ready to be used.
any bugs, issues, etc. please report to me on GitHub.

Exclusive use for GhostlyMC.

## NOTE

There are undefined behavior in this project.

**Example:**

- Constants are not defined.
- Undefined behavior in the code.
- Undefined behavior in the database.

Feel free to report any bugs, problems, etc. to me on GitHub.