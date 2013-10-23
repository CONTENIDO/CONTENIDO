<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Display current online user
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     Bilal Arsland
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2008-01-28
 *   modified 2008-02-08, Timo Trautmann, table config added
 *   modified 2008-02-12, Timo Trautmann, bugfix in getWebsiteName
 *   modified 2008-02-18, Timo Trautmann, special functions for mysql replaced
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2008-09-08, Timo Trautmann, fixed string concat bug at websitenames
 *
 *   $Id: class.activeusers.php 797 2008-09-08 11:55:33Z timo.trautmann $;
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class ActiveUsers {

	var $oDb;
	var $oCfg;
	var $oAuth;
	var $iUserId;

	/**
	 * Constructor 
	 * 
	 * @param object $db - Contenido Database Object
	 * @param object $cfg 
	 * @param object $auth 
	 * 
	 * @return  
	 **/
	function ActiveUsers($oDb, $oCfg, $oAuth) {

		$this->oCfg= $oCfg;
		$this->oAuth= $oAuth;
		$this->oDb= $oDb;

		// init db object
		if (!is_object($this->oDb) || (is_null($this->oDb))) {
			$this->oDb= new DB_Contenido;
		}

		if (!is_resource($this->oDb->Link_ID)) {
			$this->oDb->connect();
		} 

		// Load the userid
		$this->iUserId= $this->oAuth->auth["uid"];
	}

	/**
	 * Start the User Tracking:
	 * 1) First delete all inactive users with timelimit is off
	 * 2) If find user in the table, do update
	 * 3) Else there is no current user do insert new user
	 * 
	 * 
	 * @return  
	 **/
	function startUsersTracking() {

		// Delete all Contains in the table "online_user" that is older as timeout(current is 60 minutes)
		$this->deleteInactiveUser();

		$bResult= $this->findUser($this->iUserId);
		if ($bResult) {
			// update the curent user
			$this->updateUser($this->iUserId);
		} else {
			// User not found, we can insert the new user
			$this->insertOnlineUser($this->iUserId);
		}
	}

	/**
	 * Insert this user in online_user table
	 * 
	 * @param object $db - Contenido Database Object
	 * 
	 * @return  Returns true if successful else false
	 **/
	function insertOnlineUser($sUserId) {
        
		$userid = (string) $sUserId;
		$sql= "INSERT INTO `" . $this->oCfg["tab"]["online_user"] . "`(`user_id`,`lastaccessed`) VALUES('".Contenido_Security::escapeDB($userid, $this->oDb)."', NOW())";

		if ($this->oDb->query($sql)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Find the this user if exists in the table "online_user"
	 *
	 * @param integer $iUserId - Is the User-Id (get from auth object)
	 * 
	 * @return Returns true if this User is found, else false
	 **/
	function findUser($sUserId) {

		$userid = (string) $sUserId;
		$bReturn = false;
		$sql= "SELECT user_id FROM `" . $this->oCfg["tab"]["online_user"] . "` WHERE `user_id`='".Contenido_Security::escapeDB($userid, $this->oDb)."'";
		$this->oDb->query($sql);
		if ($this->oDb->next_record()) {
			$bReturn= true;
		}
		return $bReturn;
	}

	/**
	 * Find all user_ids in the table 'online_user' for get rest information from 
	 * table 'con_phplib_auth_user_md5' 
	 *
	 * 
	 * @return Returns array of user-information
	 **/
	function findAllUser() {

		$aAllUser= array ();
		$aUser= array ();
		$sWebsiteName= "";
		// get all user_ids
		$sql= "SELECT `user_id` FROM `" . $this->oCfg["tab"]["online_user"]."`";

		if ($this->oDb->query($sql) && $this->oDb->Errno == 0) {

			if ($this->oDb->num_rows() > 0) {
				while ($this->oDb->next_record()) { // Table Online User

					$aUser[]= "'" . $this->oDb->f('user_id') . "'";
				}
			}
		}
		// get data of those users
		$aAllUser= array ();

		$sSqlIn= implode(', ', $aUser); // '1','2','5','8'
		$sql= "SELECT user_id, realname, username, perms " .
		"FROM " . $this->oCfg["tab"]["phplib_auth_user_md5"] . " " .
		"WHERE user_id IN(" . $sSqlIn . ")";
        
		if ($this->oDb->query($sql) && $this->oDb->Errno == 0) {

			if ($this->oDb->num_rows() > 0) {
				while ($this->oDb->next_record()) { // Table Online User
                    $sWebsiteNames = '';
					$sUserId= $this->oDb->f("user_id");
					$aAllUser[$sUserId]['realname']= $this->oDb->f("realname");
					$aAllUser[$sUserId]['username']= $this->oDb->f("username");
					$sPerms= $this->oDb->f("perms");

					$aPerms= explode(",", $sPerms); //Alle Rechte als array in aPerms packen

					if (in_array("sysadmin", $aPerms)) {
						$aAllUser[$sUserId]['perms']= 'Systemadministrator';

					} else {

						$bIsAdmin= false;
						$iCounter= 0;
						foreach ($aPerms as $sPerm) {
                            $aResults = array();
							if (preg_match('/^admin\[(\d+)\]$/', $sPerm, $aResults)) {
								$iClientId= $aResults[1];
								$bIsAdmin= true;
								$sWebsiteName = $this->getWebsiteName($iClientId);
								if ($iCounter == 0 && $sWebsiteName != "") {
									$sWebsiteNames .= $sWebsiteName;
								} else if ($sWebsiteName != "") {
									$sWebsiteNames .= ', ' . $sWebsiteName;
								}

								$aAllUser[$sUserId]['perms']= "Administrator (" . $sWebsiteNames . ")";
								$iCounter++;

							} else if (preg_match('/^client\[(\d+)\]$/', $sPerm, $aResults) && !$bIsAdmin) {
									$iClientId= $aResults[1];
									$sWebsiteName = $this->getWebsiteName($iClientId);
									if ($iCounter == 0 && $sWebsiteName != "") {
										$sWebsiteNames .= $sWebsiteName;
									} else if ($sWebsiteName != "") {
										$sWebsiteNames .= ', ' . $sWebsiteName;
									}

									$aAllUser[$sUserId]['perms']= '(' . $sWebsiteNames . ')';
									$iCounter++;
							}

						}

					}

				}

			}
		}

		return $aAllUser;
	}

	/**
	 * This function do an update of current timestamp in "online_user"
	 *
	 * @param integer $iUserId - Is the User-Id (get from auth object)
	 * 
	 * @return  Returns true if successful, else false
	 **/
	function updateUser($sUserId) {

		$userid= (string) $sUserId;
		$sql= "UPDATE `" . $this->oCfg["tab"]["online_user"] . "` SET `lastaccessed`=NOW() WHERE `user_id`='".Contenido_Security::escapeDB($userid, $this->oDb)."'";
		if ($this->oDb->query($sql)) {
			return true;
		} else
			return false;

	}

	/**
	 * Delete all Contains in the table "online_user" that is older as 
	 * Backend timeout(currently is $cfg["backend"]["timeout"] = 60)
	 *
	 * 
	 * @return Returns true if successful else false
	 **/
	function deleteInactiveUser() {

		cInclude("includes", "config.misc.php");
		$iSetTimeOut= $this->oCfg["backend"]["timeout"];
		if ($iSetTimeOut == 0)
			$iSetTimeOut= 10;
		$sql= "";
		$sql= "DELETE FROM `" . $this->oCfg["tab"]["online_user"] . "` WHERE  DATE_SUB(now(), INTERVAL '$iSetTimeOut' Minute) >= `lastaccessed`";
		if ($this->oDb->query($sql)) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Get the number of users from the table "online_user" 
	 *
	 * 
	 * @return Returns if exists a number of users
	 **/
	function getNumberOfUsers() {

        $sql = sprintf('SELECT COUNT(*) AS cnt FROM `%s`', $this->oCfg["tab"]["online_user"]);
        $result = $this->oDb->query($sql);
        if ($result) {
            $this->oDb->next_record();
            return (int) $this->oDb->f('cnt');
        }
        return 0;
	}

	/**
	 * Delete this user from 'online user' table
	 *
	 * @param integer $iUserId - Is the User-Id (get from auth object)
	 * 
	 * @return  Returns true if successful, else false
	 **/
	function deleteUser($sUserId) {

		$userid= (string) $sUserId;
		$sql= "DELETE FROM `" . $this->oCfg["tab"]["online_user"] . "` WHERE `user_id` = '".Contenido_Security::escapeDB($userid, $this->oDb)."'";

		if ($this->oDb->query($sql)) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Get the website name from table con_clients
      * @modified Timo Trautmann: Local database instance needed, because this function ist used in 
      *                           findAllUser(). findAllUser() already uses this connection
	 *
	 * @param integer $iIdClient is the Client id
	 * 
	 * @return  Returns the name if successful
	 **/
	function getWebsiteName($iIdClient) {
        $oDbLocal = new DB_contenido();
		$iClientId= (int) $iIdClient;
		$sql= "";
		$sName= "";
		$sql= "SELECT `name` as myname  FROM `" . $this->oCfg["tab"]["clients"] . "` WHERE `idclient` = '".Contenido_Security::toInteger($iClientId)."'";
		$oDbLocal->query($sql);
		if ($oDbLocal->next_record()) {
			$sName= $oDbLocal->f('myname');
		}

		return $sName;
	}
}
?>