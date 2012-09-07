<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Controls all Contenido backend actions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    0.1.0
 * @author     Jan Lengowski
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
 *   $Id: class.backend.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Contenido_Backend {

    /**
     * Debug flag
     */
    var $debug = 0;
 
    /**
     * Possible actions
     * @var array
     */
     var $actions = array();

    /**
     * Files
     * @var array
     */
     var $files = array();

    /**
     * Stores the frame number
     * @var int
     */
    var $frame = 0;

    /**
     * Errors
     * @var array
     */
     var $errors = array();

    /**
     * Save area
     * @var string
     */
     var $area = '';

    /**
     * Constructor
     */
    function Contenido_Backend() {
        # do nothing
    } # end function

    /**
     * Set the frame number
     * in which the file is
     * loaded
     * @return void
     */
    function setFrame($frame_nr = 0) {
		$frame_nr = Contenido_Security::toInteger($frame_nr);
        $this->frame = $frame_nr;
        
    } # end function


    /**
     * Loads all required data
     * from the DB and stores it
     * in the $actions and $files array
     *
     * @param $area string selected area
     * @return
     */
    function select($area) {
        # Required global vars
        global $cfg, $client, $lang, $db, $perm, $action, $idcat;
        global $idcat, $idtpl, $idmod, $idlay;

        if (isset($idcat)) {
            $itemid = $idcat;
        } elseif (isset($idtpl)) {
            $itemid = $idtpl;
        } elseif (isset($idmod)) {
            $itemid = $idmod;
        } elseif (isset($idlay)) {
            $itemid = $idlay;
        } else {
            $itemid = 0;
        }
		
		$itemid = Contenido_Security::toInteger($itemid);
		$area	= Contenido_Security::escapeDB($area, $db);
        
        # Store Area
        $this->area = $area;

        # extract actions
        $sql = "SELECT
                    b.name AS name,
                    b.code AS code,
                    b.relevant as relevant_action,
                    a.relevant as relevant_area
                FROM
                    ".$cfg["tab"]["area"]." AS a,
                    ".$cfg["tab"]["actions"]." AS b
                WHERE
                    a.name   = '".$area."' AND
                    b.idarea = a.idarea AND
                    a.online = '1'";

        # Check if the user has
        # access to this area.
        # Yes -> Grant him all actions
        # No  -> Grant him only action
        #        which are irrelevant
        #        = (Field 'relevant' is 0)

        if (!$perm->have_perm_area_action($area)) {
            $sql .= " AND a.relevant = '0'";
        }

        $db->query($sql);

        while ($db->next_record()) {

                # Save the action only access to
                # the desired action is granted.
                # If this action is relevant for rights
                # check if the user has permission to
                # execute this action

                if ($db->f("relevant_action") == 1 && $db->f("relevant_area") == 1) {

					if ($perm->have_perm_area_action_item($area, $db->f("name"), $itemid)) {
                        $this->actions[$area][$db->f('name')] = $db->f('code');
                    }

                    if ($itemid == 0) {
                        // itemid not available, since its impossible the get the correct rights out
                        // we only check if userrights are given for these three items on any item
                        if ($action=="mod_edit" || $action=="tpl_edit" || $action=="lay_edit") {
                            if ($perm->have_perm_area_action_anyitem($area, $db->f("name"))) {
                                $this->actions[$area][$db->f('name')] = $db->f('code');
                            }
                        }
                    }

                } else {
                    $this->actions[$area][$db->f('name')] = $db->f('code');

                }

                
        } # end while

		$sql = "SELECT
                    b.filename AS name,
                    b.filetype AS type,
                    a.parent_id AS parent_id
                FROM
                    ".$cfg['tab']['area']." AS a,
                    ".$cfg['tab']['files']." AS b,
                    ".$cfg['tab']['framefiles']." AS c
                WHERE
                    a.name    = '".$area."' AND
                    b.idarea  = a.idarea AND
                    b.idfile  = c.idfile AND
                    c.idarea  = a.idarea AND
                    c.idframe = '".$this->frame."' AND
                    a.online  = '1'";
               
          # Check if the user has
        # access to this area.
        # Yes -> Extract all files
        # No  -> Extract only irrelevant
        #        Files = (Field 'relevant' is 0)
      if (!$perm->have_perm_area_action($area)) {
              $sql .= " AND a.relevant = '0'";
        }
        $sql .= " ORDER BY b.filename";

        $db->query($sql);

        while ($db->next_record()) {

            # Test if entry is a plug-in.
            # If so don't add the Include path
            if (strstr($db->f('name'), "/")) {
                $filepath = $cfg["path"]["plugins"] . $db->f('name');
            } else {
                $filepath = $cfg["path"]["includes"] . $db->f('name');
            }

            # If filetype is Main AND
            # parent_id is 0 file is
            # a sub file
            if ($db->f('parent_id') != 0 && $db->f('type') == 'main'){
                $this->files['sub'][] = $filepath;
            }
            
            $this->files[$db->f('type')][] = $filepath;
        } # end while

        if ($this->debug) {
            echo '<pre style="font-family: verdana; font-size: 10px">';
            echo "<b>Na, wieder scheisse gebaut?? ;-)</b>\n\n";
            echo "<b>Files:</b>\n\n";
            print_r($this->files);
            echo "\n\n<b>Actions:</b>\n\n";
            print_r($this->actions[$this->area]);
            echo "\n\n<b>Information:</b>\n\n";
            echo "Area: $area\n";
            echo "Action: $action\n";
            echo "Client: $client\n";
            echo "Lang: $lang\n";
            echo '</pre>';
        }


    } # end function

    /**
     * Checks if choosen action exists.
     * If so, execute/eval it.
     *
     * @param $action String Action to execute
     * @return $action String Code for selected Action
     */
    function getCode($action) {
        global $notification;

        if (isset($this->actions[$this->area][$action])) {

            return ($this->actions[$this->area][$action]);
            
        } else {

            # There is no action or
            # user has no access to
            # it
        }

    } # end function

    /**
     * Returns the specified file path.
     * Distinction between 'inc' and 'main'
     * files.
     *
     * 'inc'  => Required file like functions/classes etc.
     * 'main' => Main file
     *
     * @param $which String 'inc' / 'main'
     */
    function getFile($which) {

        if (isset($this->files[$which])) {

            return $this->files[$which];
            
        } else {

            # There is no action or
            # user has no access to
            # it
        }

    } # end function


    /**
     * Creates a log entry for the specified parameters.
     *
     * @param $idcat  Category-ID
     * @param $idart  Article-ID
     * @param $client Client-ID
     * @param $lang   Language-ID
     * @param $action Action (ID or canonical name)
     */
    function log($idcat, $idart, $client, $lang, $idaction) {
        global $perm, $auth, $cfg, $classarea, $area;

        $db_log = new DB_Contenido;

        $lastentry = $db_log->nextid($cfg["tab"]["actionlog"]);

        $timestamp = date("Y-m-d H:i:s");
        $idcatart = "0";
		
		$idcat 		= Contenido_Security::toInteger($idcat);
		$idart 		= Contenido_Security::toInteger($idart);
		$client 	= Contenido_Security::toInteger($client);
		$lang 		= Contenido_Security::toInteger($lang);
		$idaction 	= Contenido_Security::escapeDB($idaction, $db_log);
		$area		= Contenido_Security::escapeDB($area, $db_log);

        if (!Contenido_Security::isInteger($client)) { return; }
        if (!Contenido_Security::isInteger($lang)) { return; }

        if (isset($idcat) && isset($idart) && $idcat != "" && $idart != "")
        {		
            $sql = "SELECT idcatart
                        FROM
                       ". $cfg["tab"]["cat_art"] ."
                    WHERE
                        idcat = '".$idcat."' AND
                        idart = '".$idart."'";
    
            $db_log->query($sql);
    
            $db_log->next_record();
            $idcatart = $db_log->f("idcatart");
        }
   
        $oldaction = $idaction;
        $idaction = $perm->getIDForAction($idaction);    
		
        if ($idaction != "") 
        {
        $sql = "INSERT INTO
                    ". $cfg["tab"]["actionlog"]."
                SET
                    idlog = '".$lastentry."',
                    user_id = '".$auth->auth["uid"]."',
                    idclient = '".$client."',
                    idlang = '".$lang."',
                    idaction = '".$idaction."',
                    idcatart = '".$idcatart."',
                    logtimestamp = '".$timestamp."'";

        } else {
           echo $oldaction. " is not in the actions table!<br><br>";
           echo "Use the following statement to insert it with minimal functionsinto the actions table:<br>";
           echo "<code>";
           $myareaid = $classarea->getAreaID($area);

            $sql = "SELECT max(idaction) FROM " . $cfg["tab"]["actions"];
            $db_log->query($sql);
            $db_log->next_record();
    
            $mynextid = $db_log->f(0) + 1;


           echo "INSERT INTO ". $cfg["tab"]["actions"]."
                      SET idaction = '".$mynextid."', idarea = '".$myareaid."', name = '".$oldaction."', relevant = '1'";
           echo "</code>";
            
        }
            $db_log->query($sql);
    }
} # end class Contenido_Backend
?>
