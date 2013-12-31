<?php
/**
 * Plexis Content Management System
 *
 * @copyright   2011-2012, Plexis Dev Team
 * @license     GNU GPL v3
 * @package     Wowlib
 */
namespace System\Wowlib;

class Account // implements iAccount
{
	/**
	 * The realm database connection
	 * @var \System\Database\DbConnection
	 */
	protected $DB;

	/**
	 * The emulator driver config
	 * @var Driver
	 */
	protected $config = array();

	/**
	 * Database column names
	 * @var array
	 */
	protected $cols = array();

	/**
	 * Indicated whether the password was changed
	 * @var bool
	 */
	protected $changed = false;

	/**
	 * Our temporary password when the setPassword method is called
	 * @var string
	 */
	protected $password;

	/**
	 * The user's data array
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor
	 *
	 * @param array $data The account data from the database
	 * @param Driver $config The emulators driver config
	 * @param \System\Database\DbConnection $Db The realm database connection
	 *
	 * @throws \Exception Thrown if the account ID doesn't exist
	 *
	 */
	public function __construct($data, $config, $Db)
	{
		// If the result is NOT false, we have a match, username is taken
		if(!is_array($data)) throw new \Exception('Account Doesnt Exist');

		// Set local variables
		$this->DB = $Db;
		$this->data = $data;
		$this->config = $config;
		$this->cols = $config->getColumns('account');
	}

	/**
	 * Saves the edited data to the realm database
	 *
	 * @return bool
	 */
	public function save()
	{
		// First we have to check if the username was changed
		if($this->changed)
		{
			if(empty($this->password)) return false;

			// Make sure the sha hash is set correctly
			$this->setPassword($this->password);
		}

		// Fetch our table name, and ID column for the query
		$table = $this->config->getTableById('account');
		$col = $this->cols['id'];

		return ($this->DB->update($table, $this->data, "`{$col}`= ". $this->data[$col]) !== false);
	}

	/**
	 * Fetches the accounts ID
	 *
	 * @return int
	 */
	public function getId()
	{
		// Fetch our column name
		$col = $this->cols['id'];
		return (int) $this->data[$col];
	}

	/**
	 * Fetches the accounts username
	 *
	 * @return string
	 */
	public function getUsername()
	{
		// Fetch our column name
		$col = $this->cols['username'];
		return $this->data[$col];
	}

	/**
	 * Fetches the accounts email address
	 *
	 * @return bool|string Returns false if the email is not stored in the accounts table
	 */
	public function getEmail()
	{
		// Fetch our column name
		$col = $this->cols['email'];
		if(!$col) return false;

		return $this->data[$col];
	}

	/**
	 * Fetches the join date for the account
	 *
	 * @param bool $asTimestamp Return the join date as a timestamp?
	 *
	 * @return bool|int Returns false if the join date is not stored in the accounts table
	 */
	public function joinDate($asTimestamp = false)
	{
		// Fetch our column name
		$col = $this->cols['joindate'];
		if(!$col) return false;

		return ($asTimestamp == true) ? strtotime($this->data[$col]) : $this->data[$col];
	}

	/**
	 * Fetches the last login date for the account (ingame)
	 *
	 * @param bool $asTimestamp Return the date as a timestamp?
	 *
	 * @return bool|int Returns false if the last login date is not stored in the accounts table
	 */
	public function lastLogin($asTimestamp = false)
	{
		// Fetch our column name
		$col = $this->cols['lastLogin'];
		if(!$col) return false;

		return ($asTimestamp == true) ? strtotime($this->data[$col]) : $this->data[$col];
	}

	/**
	 * Fetches the last known IP for this accounts user
	 *
	 * @return bool|string Returns false if the accounts table does not store this information
	 */
	public function getLastIp()
	{
		// Fetch our column name
		$col = $this->cols['lastIp'];
		if(!$col) return false;

		return $this->data[$col];
	}

	/**
	 * Returns whether the account is locked or not.
	 *
	 * @return bool
	 */
	public function isLocked()
	{
		// Fetch our column name
		$col = $this->cols['locked'];
		if(!$col) return false;

		return (bool) $this->data[$col];
	}

	/**
	 * Fetches the expansion level for this account
	 *
	 * @param bool $asText If set to true, the expansions name will be returned instead
	 *      of the expansions ID
	 *
	 * @return int|string
	 */
	public function getExpansion($asText = false)
	{
		// We need to convert the bit value to normal
		$exp = $this->data[ $this->cols['expansion'] ];
		$val = array_search($exp, $this->config->get('expansionToBit'));

		return ($asText == true) ? $this->expansionToText($val) : (int) $val;
	}

	/**
	 * Sets a new password for this account
	 *
	 * @param string $password The new (unencrypted) password
	 *
	 * @return bool Returns false only if password is less then 3 chars.
	 */
	public function setPassword($password)
	{
		// Remove whitespace in password
		$password = trim($password);
		if(strlen($password) < 3) return false;

		// Set our passwords
		$this->password = $password;
		if($this->cols['shaPassword'])
			$this->data[ $this->cols['shaPassword'] ] = sha1(strtoupper($this->data['username'] .':'. $password));
		if($this->cols['password']) $this->data[ $this->cols['password'] ] = $password;
		if($this->cols['sessionkey']) $this->data[ $this->cols['sessionkey'] ] = null;
		if($this->cols['v']) $this->data[ $this->cols['v'] ] = null;
		if($this->cols['s']) $this->data[ $this->cols['s'] ] = null;
		return true;
	}

	/**
	 * Sets a new username for this account
	 *
	 * @param string $username The new username
	 *
	 * @return bool Returns false only if username is less then 3 chars.
	 */
	public function setUsername($username)
	{
		// Remove whitespace
		$username = trim($username);
		if(strlen($username) < 3) return false;

		// Set our username if its not the same as before
		if($username != $this->data['username'])
		{
			$this->changed = true;
			$this->data[ $this->cols['username'] ] = $username;
		}

		return true;
	}

	/**
	 * Sets a new email address for this account
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function setEmail($email)
	{
		if(!$this->cols['email']) return false;
		$this->data[ $this->cols['email'] ] = $email;
		return true;
	}

	/**
	 * Sets the expansion level of the account
	 *
	 * @param int $e The expansion ID
	 *   0 => None, Base Game
	 *   1 => Burning Crusade
	 *   2 => WotLK
	 *   3 => Cata (If Supported)
	 *   4 => MoP (If Supported)
	 *
	 * @return bool
	 */
	public function setExpansion($e)
	{
		if(!$this->cols['expansion']) return false;
		$this->data[ $this->cols['expansion'] ] = $this->expansionToBit($e);
		return true;
	}

	/**
	 * Sets whether this account is locked or not
	 *
	 * @param bool $locked
	 *
	 * @return bool
	 */
	public function setLocked($locked)
	{
		// Get our column name
		$col = $this->cols['locked'];
		if(!$col) return false;

		// Set to an integer
		$this->data[ $col ] = ($locked == true) ? 1 : 0;

		return true;
	}

	/**
	 * Converts an expansion ID to the official string Name
	 *
	 * @param int $id The expansion ID
	 *
	 * @return bool
	 */
	protected function expansionToText($id = 0)
	{
		// return all expansions if no id is passed
		$exp = array(
			0 => 'Classic',
			1 => 'The Burning Crusade',
			2 => 'Wrath of the Lich King',
			3 => 'Cataclysm',
			4 => 'Mists Of Pandaria'
		);
		return (isset($exp[$id])) ? $exp[$id] : false;
	}

	/**
	 * Returns the Database ID of the given expansion
	 *
	 * @param int $e
	 *
	 * @return bool|int
	 */
	protected function expansionToBit($e)
	{
		if(!isset($this->config['expansionToBit'][$e])) return false;
		return (int) $this->config['expansionToBit'][$e];
	}
}