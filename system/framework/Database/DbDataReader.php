<?php

namespace System\Database;

/**
 * DbDataReader represents a forward-only stream of rows from a query result set.
 */
class DbDataReader
{
    protected $statement;

    /**
     * Constructor.
     *
     * @param DbCommand $Command
     *
     * @internal param \System\Database\DbCommand $command the command generating the query result
     */
    public function __construct(DbCommand $Command)
    {
        $this->statement = $Command->GetStatement();
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);
    }

    /**
     * Advances the reader to the next row in a result set.
     *
     * @return array|bool the current row, false if no more row available
     */
    public function Read()
    {
        return $this->statement->fetch();
    }

    public function ReadAll()
    {

    }

    /**
     * Fetches a column from the last query result
     *
     * @param int $col The column index id
     * @return mixed|bool Returns false if there was no result, or
     *   the value of the column
     */
    public function ReadColumn($col = 0)
    {
        // Make sure we don't have a false return
        if($this->statement == false || $this->statement == null) return false;
        return $this->statement->fetchColumn($col);
    }

    /**
     * Returns the number of columns in the result set.
     */
    public function GetColumnCount()
    {
        return $this->statement->coulmnCount();
    }

    /**
     * Returns the number of columns in the result set.
     */
    public function GetRowCount()
    {
        $regex = '/^SELECT (.*) FROM (.*)$/i';

        // Make sure this is a SELECT statement we are dealing with
        if(preg_match($regex, $this->query, $output) != false)
        {
            // Query and get our count
            $query = "SELECT COUNT(*) FROM ". $output[2];

            // Prepare the statement
            $stmt = $this->prepare( $query );
            try {
                $stmt->execute( $this->sprints );
            }
            catch (\PDOException $e) {

            }

            return $stmt->fetchColumn();
        }
        else
            return $this->result->rowCount();
    }
}