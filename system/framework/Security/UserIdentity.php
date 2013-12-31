<?php
/**
 * Plexis Content Management System
 *
 * @file        system/framework/Security/UserIdentity.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\Security;
use System\Http\WebRequest;
use System\Utils\LogWritter;
use System\Wowlib\Account;
use System\Wowlib\Server;

/**
 * UserIdentity Class
 *
 * @author      Steven Wilson 
 * @package     System
 * @subpackage  Security
 */
final class UserIdentity
{
	/**
	 * The user's ID... if 0, means user is a guest
	 * @var int
	 */
	protected $userId = 0;

	/**
	 * @var null|\System\Wowlib\Account
	 */
	protected $account;

	/**
	 * Indicates whether this user identity is the website's root admin (Owner)
	 * @var bool
	 */
	protected $isOwner = false;

	/**
	 * User variables
	 * @var mixed[]
	 */
	protected $variables = array();

	/**
	 * An array of user permissions
	 * @var string[]
	 */
	protected $permissions = array();


	/**
	 * Constructor
	 *
	 * @param int $userId The user's ID we are loading an identity for. Set as 0 for guest
	 * @param \System\Wowlib\Account|null $account
	 */
	public function __construct($userId = 0, $account = null)
	{
		// Set internal vars
		$this->userId = $userId;
		$this->account = $account;

		// Initialize user variables
		$this->_initUser();

		// Update user group permissions
		$this->_updatePerms();
	}

	/**
	 * This method is used to return whether the user have a specific permission
	 *
	 * @param string $operation The name of the operation we are checking
	 *   permissions for
	 * @return bool
	 */
	public function checkAccess($operation)
	{
		return ($this->isOwner) ? true : in_array($operation, $this->permissions);
	}

	/**
	 * Returns whether this user identity is a guest
	 *
	 * @return bool
	 */
	public function isGuest()
	{
		return ($this->userId == 0);
	}

	/**
	 * Indicates whether this user identity is the website's root admin (Owner)
	 * @return bool
	 */
	public function isOwner()
	{
		return $this->isOwner;
	}

	/**
	 * Returns the user variables as an array
	 *
	 * @return mixed[]
	 */
	public function asArray()
	{
		return $this->variables;
	}

	/**
	 * @todo Wow lib account stuff
	 * @throws \Exception Thrown if the User cannot be loaded from any database
	 */
	protected function _initUser()
	{
		// Init vars
		$Log = LogWritter::Instance('debug');
		$DB = \Plexis::Database();

		// Non guest
		if($this->userId != 0)
		{
			// Load user from the plexis database
			$query = "SELECT
						`username`
						`activated`,
						`pcms_accounts`.`group_id`,
						`last_seen`,
						`registered`,
						`registration_ip`,
						`selected_theme`,
						`votes`,
						`vote_points`,
						`vote_points_earned`,
						`vote_points_spent`,
						`donations`,
						`_account_recovery`,
						`pcms_account_groups`.`title`,
						`pcms_account_groups`.`is_banned`,
						`pcms_account_groups`.`is_user`,
						`pcms_account_groups`.`is_admin`,
						`pcms_account_groups`.`is_super_admin`,
						`pcms_account_groups`.`permissions`
					FROM `pcms_accounts` INNER JOIN `pcms_account_groups` ON
					pcms_accounts.group_id = pcms_account_groups.group_id WHERE `id` = ?";

			// Query our database and get the users information
			$result = $DB->query( $query, array($this->userId) )->fetch();

			// If the user doesn't exists in the table, we need to insert it
			if(!is_array($result))
			{
				if($this->account instanceof Account)
				{
					LoadUser:
					{
						// Add trace for debugging
						$Log->logDebug("[UserIdentity] User account '{$this->account->getUsername()}' doesnt exist in Plexis database, fetching account from realm");
						$data = array(
							'id' => $this->account->getId(),
							'username' => ucfirst(strtolower($this->account->getUsername())),
							'email' => $this->account->getEmail(),
							'activated' => 1,
							'registered' => ($this->account->joinDate() == false)
								? date("Y-m-d H:i:s", time())
								: $this->account->joinDate(),
							'registration_ip' => WebRequest::ClientIp()
						);
						$DB->insert( 'pcms_accounts', $data );
						$result = $DB->query( $query )->fetch();

						// If the insert failed, we have a fatal error
						if(!is_array($result))
						{
							// Add trace for debugging
							$Log->logError("[UserIdentity] There was a fatal error trying to insert account data into the plexis database");

							// Raise an exception!
							throw new \Exception("Unable to insert new user data into the plexis database!");
						}
					}
				}
				else
				{
					// try and load the account manually
					$Server = \Plexis::GetServer();
					if($Server instanceof Server)
					{
						$this->account = $Server->fetchAccount($this->userId);
						if($this->account instanceof Account)
							goto LoadUser;
					}

					// raise an exception!
					throw new \Exception("User Id (". $this->userId . ") Does not exist in the plexis database!");
				}
			}
		}
		else
		{
			// Guest
			$query = "SELECT * FROM pcms_account_groups WHERE group_id=1";
			$result = $DB->query($query)->fetch();
			$result['username'] = 'Guest';
		}

		// Do permissions!
		$perms = unserialize($result['permissions']);
		$this->permissions = (!empty($perms)) ? array_keys($perms) : array();
		unset($result['permissions']);

		// Set user vars
		$this->variables = $result;
	}

	/**
	 * Removes old permissions that are no longer in effect, and updates
	 * the user groups permissions if some old permissions were removed
	 */
	protected function _updatePerms()
	{
		// Get a list of all existing permissions
		$DB = \Plexis::Database();
		$query = "SELECT `key` FROM `pcms_permissions`";
		$list = $DB->query( $query )->fetchAll( \PDO::FETCH_COLUMN );

		// Only keep permissions that are still in effect (Not removed)
		$size = count($this->permissions);
		$this->permissions = array_intersect($this->permissions, $list);

		// Remove old, deleted permissions that don't exist anymore
		if($size != count($this->permissions))
		{
			// Convert permissions into database format
			$newlist = array();
			foreach($this->permissions as $p)
				$newlist[$p] = 1;

			// Update the database
			$DB->update(
				'pcms_account_groups',
				array('permissions' => serialize( $newlist )),
				"`group_id`=".$this->variables['group_id']
			);
		}
	}
}