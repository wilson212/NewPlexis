<?php

namespace System\Database;

class SqlException extends \Exception
{
    protected $query;
    protected $errorInfo;

    public function __construct($errorInfo, $query, \Exception $previous = null)
    {
        parent::__construct($errorInfo[2], 0, $previous);
        $this->query = $query;
        $this->errorInfo = $errorInfo;
    }

    public function getPdoCode()
    {
        return $this->errorInfo[0];
    }

    public function getDriverCode()
    {
        return $this->errorInfo[1];
    }

    public function getQuery()
    {
        return $this->query;
    }
}