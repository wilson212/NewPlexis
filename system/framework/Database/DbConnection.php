<?php

namespace System\Database;
use PDO;

class DbConnection extends PDO
{
    /**
     * The result of the last query
     */
    public $result;

    public function __construct($server, $port, $dbname, $username, $password)
    {
        // Connect using the PDO Constructor
        $dsn = "mysql:host={$server};port={$port};dbname={$dbname}";
        parent::__construct($dsn, $username, $password, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
    }

    public function Query($query)
    {
        return parent::query($query);
    }

    public function CreateCommand($queryString)
    {
        return new DbCommand($queryString, $this);
    }
}