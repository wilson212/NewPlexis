<?php

namespace System\Database;
use PDO;
use PDOException;

/**
 * SqlCommand represents an SQL statement to execute against a database
 */
class DbCommand
{
	protected $connection;
	protected $query;
	protected $params = array();
	protected $statement;
	
	protected $queryError;
	
	public function __construct($query = null, DbConnection $connection)
	{
		$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->connection = $connection;
		$this->query = $query;
		$this->statement = $this->connection->prepare($query);
	}
	
	public function ExecuteReader()
	{
		//$query = $this->query;
		//foreach($this->params as $param)
			//$query = str_replace($param->key, ($param->type == PDO::PARAM_STR) ? $this->connection->quote($param->value) : $param->value, $this->query);
		
		$result = false;
		try {
			$this->statement->execute();
		}
		catch(PDOException $e) {
			throw new SqlException($this->connection->errorInfo(), $this->query, $e);
		}
		
		return new DbDataReader($this);
	}
	
	public function ExecuteNonQuery()
	{
		$result = false;
		try {
			$this->statement->execute();
		}
		catch(PDOException $e) {
			throw new SqlException($this->connection->errorInfo(), $this->query, $e);
		}
		
		return $this->statement;
	}
	
	public function BindParam($key, $value, $type = PDO::PARAM_STR)
	{
		//$this->params[$key] = new SqlParam($key, $value, $type);
		$this->statement->bindParam($key, $value, $type);
	}
	
	public function ErrorCode()
	{
		return $this->connection->errorCode();
	}
	
	public function GetStatement()
	{
		return $this->statement;
	}
}