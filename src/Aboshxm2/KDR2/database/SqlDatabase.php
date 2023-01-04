<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\database;

use Aboshxm2\KDR2\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class SqlDatabase implements Database
{
    private DataConnector $database;

    public function __construct(Main $plugin, array $data)
    {
        $this->database = libasynql::create($plugin, $data, [
            "sqlite" => "sql/sqlite.sql",
            "mysql" => "sql/mysql.sql"
        ]);
        $this->database->executeGeneric("KDR2.init", [], null, function (SqlError $error) {
            throw new \RuntimeException($error->getMessage());
        });
        $this->database->waitAll();
    }

    public function close()
    {
        $this->database->close();
    }

    /**
     * @inheritDoc
     */
    public function getAll(string $playerName, \Closure $then): void
    {
        $this->database->executeSelect("KDR2.selectPlayer", ["playerName" => $playerName], function (array $rows) use ($then): void {
            if (count($rows) > 0) {
                $then($rows[0]["kills"], $rows[0]["deaths"], $rows[0]["killstreak"]);
            } else {
                $then(0, 0, 0);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function setAll(string $playerName, int $kills, int $deaths, int $killstreak): void
    {
        $this->database->executeInsert("KDR2.insertOrUpdate", ["playerName" => $playerName, "kills" => $kills, "deaths" => $deaths, "killstreak" => $killstreak]);
    }
}
