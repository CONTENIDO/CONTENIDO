<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido client class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.client.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Client {

    /**
     * Constructor Function
     * @param
     */
    function Client() {
        // empty
    } // end function

    /**
     * getAvailableClients()
     * Returns all clients available in the system
     * @return array   Array with id and name entries
     */
    function getAvailableClients() {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idclient,
                    name
                FROM
                ". $cfg["tab"]["clients"];
        $db->query($sql);

        $clients = array();
        
        while ($db->next_record())
        {
            $newentry["name"] = $db->f("name");
            $clients[$db->f("idclient")] = $newentry;
        }

        return ($clients);
    } // end function

    /**
     * getAvailableClients()
     * Returns all clients available in the system
     * @return array   Array with id and name entries
     */
    function getAccessibleClients() {
        global $cfg, $perm;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idclient,
                    name
                FROM
                ". $cfg["tab"]["clients"]." ORDER BY idclient ASC";
        $db->query($sql);

        $clients = array();
        
        while ($db->next_record())
        {
            if ($perm->have_perm_client("client[".$db->f("idclient")."]") ||
                $perm->have_perm_client("admin[".$db->f("idclient")."]") ||
                $perm->have_perm_client())
            {
                $newentry["name"] = $db->f("name");
                $clients[$db->f("idclient")] = $newentry;
            }

        }

        return ($clients);
    } // end function
    
    /**
     * getClientname()
     * Returns the clientname of the given clientid
     * @return string  Clientname if found, or emptry string if not.
     */
    function getClientname ($clientid)
    {
        global $cfg;
		$clientid = Contenido_Security::toInteger($clientid);

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["clients"]."
                WHERE
                    idclient = '".$clientid."'";

        $db->query($sql);
        if ($db->next_record())
        {
            return ($db->f("name"));
        } else {
            return i18n("No client");
        }

    } // end function

    /**
     * hasLanguageAssigned()
     * Returns if the given client has a language
     * @return bool  true if the client has a language
     */
    function hasLanguageAssigned ($clientid)
    {
        global $cfg;
		$clientid = Contenido_Security::toInteger($clientid);

        $db = new DB_Contenido;

        $sql = "SELECT
                    idlang
                FROM
                ". $cfg["tab"]["clients_lang"]."
                WHERE
                    idclient = '".$clientid."'";

        $db->query($sql);
        if ($db->next_record())
        {
            return (true);
        } else {
            return (false);
        }

    } // end function
    
} // end class

?>
