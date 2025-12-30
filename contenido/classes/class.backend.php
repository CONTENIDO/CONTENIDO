<?php

/**
 * This file contains the backend class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class controls all backend actions.
 *
 * @package    Core
 * @subpackage Backend
 */
class cBackend
{

    /**
     * @var array Possible actions
     */
    protected $_actions = [];

    /**
     * @var array
     */
    protected $_files = [];

    /**
     * @var int Stores the frame number
     */
    protected $_frame = 0;

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @var string Save area
     */
    protected $_area = '';

    /**
     * @var array Configuration array
     */
    protected $_cfg;

    public function __construct()
    {
        $this->_cfg = cRegistry::getConfig();
    }

    /**
     * Set the frame number in which the file is loaded.
     *
     * @param int $frame [optional]
     */
    public function setFrame($frame = 0)
    {
        $this->_frame = cSecurity::toInteger($frame);
    }

    /**
     * Loads all required data from the DB and stores it in the $_actions and $_files array.
     *
     * @param string $area Selected area
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function select($area)
    {
        // Required global vars
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

        $itemid = cSecurity::toInteger($itemid);
        $db = cRegistry::getDb();
        $perm = cRegistry::getPerm();
        $action = cRegistry::getAction();

        $area = $db->escape($area);

        $sqlStatements = [];

        // Store Area
        // @todo Seems not necessary, it is used nowhere else in this class.
        $this->_area = $area;

        // extract actions
        $sql = 'SELECT
                    b.name AS name,
                    b.code AS code,
                    b.relevant as relevant_action,
                    a.relevant as relevant_area
                FROM
                    ' . cRegistry::getDbTableName('area') . ' AS a,
                    ' . cRegistry::getDbTableName('actions') . " AS b
                WHERE
                    a.name   = '" . $area . "' AND
                    b.idarea = a.idarea AND
                    a.online = 1";

        // Check if the user has access to this area.
        // Yes -> Grant him all actions
        // No -> Grant him only action which are irrelevant (i.e. 'relevant' is 0)
        if (!$perm->have_perm_area_action($area)) {
            $sql .= " AND a.relevant = 0";
        }

        $sqlStatements[] = $sql;
        $db->query($sql);

        while ($db->nextRecord()) {
            $name = $db->f('name');
            $code = $db->f('code');

            // Save the action only access to the desired action is granted.
            // If this action is relevant for rights check if the user has
            // permission to execute this action
            if ($db->f('relevant_action') == 1 && $db->f('relevant_area') == 1) {
                if ($perm->have_perm_area_action_item($area, $name, $itemid)) {
                    $this->_actions[$area][$name] = $code;
                }

                if ($itemid == 0) {
                    // itemid not available, since its impossible the get the
                    // correct rights out
                    // we only check if user-rights are given for these three
                    // items on any item
                    if ($action == 'mod_edit' || $action == 'tpl_edit' || $action == 'lay_edit') {
                        if ($perm->have_perm_area_action_anyitem($area, $name)) {
                            $this->_actions[$area][$name] = $code;
                        }
                    }
                }
            } else {
                $this->_actions[$area][$name] = $code;
            }
        }

        $sql = 'SELECT
                    b.filename AS name,
                    b.filetype AS type,
                    a.parent_id AS parent_id
                FROM
                    ' . cRegistry::getDbTableName('area') . ' AS a,
                    ' . cRegistry::getDbTableName('files') . ' AS b,
                    ' . cRegistry::getDbTableName('framefiles') . " AS c
                WHERE
                    a.name    = '" . $area . "' AND
                    b.idarea  = a.idarea AND
                    b.idfile  = c.idfile AND
                    c.idarea  = a.idarea AND
                    c.idframe = " . $this->_frame . " AND
                    a.online  = 1";

        // Check if the user has access to this area.
        // Yes -> Extract all files
        // No -> Extract only irrelevant files (i.e. 'relevant' is 0)
        if (!$perm->have_perm_area_action($area)) {
            $sql .= " AND a.relevant = 0";
        }

        $sql .= ' ORDER BY b.filename';

        $sqlStatements[] = $sql;
        $db->query($sql);

        while ($db->nextRecord()) {
            $name = $db->f('name');
            // Test if entry is a plug-in. If so don't add the Include path
            if (strstr($name, '/')) {
                $filepath = $this->_cfg['path']['plugins'] . $name;
            } else {
                $filepath = $this->_cfg['path']['includes'] . $name;
            }

            // If filetype is Main AND parent_id is 0 file is a sub file
            if ($db->f('parent_id') != 0 && $db->f('type') == 'main') {
                $this->_files['sub'][] = $filepath;
            }

            $this->_files[$db->f('type')][] = $filepath;
        }

        $actions = !empty($this->_actions[$this->_area]) ? $this->_actions[$this->_area] : [];
        $debug = "cBackend: Files:\n" . print_r($this->_files, true) . "\n"
            . "Actions:\n" . print_r($actions, true) . "\n"
            . "Information:\n"
            . "  - Area: $area\n"
            . "  - Action: $action\n"
            . "  - Client: " . cRegistry::getClientId() . "\n"
            . "  - Lang: " . cRegistry::getLanguageId() . "\n"
            . "SQL statements:" . print_r($sqlStatements, true) . "\n";
        cDebug::out($debug);
    }

    /**
     * Return code of action.
     * Checks if code file for given action exists. If so, read and return it else return an empty string.
     *
     * @param string $action Action to be read
     * @return string Code for given action
     * @throws cInvalidArgumentException
     */
    public function getCode(string $action): string
    {
        $actionCodeFile = cRegistry::getBackendPath() . 'includes/type/action/include.' . $action . '.action.php';
        if (cFileHandler::exists($actionCodeFile)) {
            return cFileHandler::read($actionCodeFile);
        }

        return '';
    }

    /**
     * Returns the specified file path. Distinction between 'inc' and 'main' files.
     * - 'inc': Required file like functions/classes etc.
     * - 'main': Main file
     *
     * @param string $which 'inc' / 'main'
     * @return array List of files
     */
    public function getFile(string $which): array
    {
        if (isset($this->_files[$which]) && is_array($this->_files[$which])) {
            return $this->_files[$which];
        } else {
            return [];
        }
    }

    /**
     * Creates a log entry for the specified parameters.
     *
     * @param int $categoryId Category-ID
     * @param int $articleId Article-ID
     * @param int $clientId Client-ID
     * @param int $languageId Language-ID
     * @param int|string $action Action (ID or canonical name)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function log($categoryId, $articleId, $clientId, $languageId, $action)
    {
        $clientId = cSecurity::toInteger($clientId);
        $languageId = cSecurity::toInteger($languageId);

        if ($clientId <= 0 || $languageId <= 0) {
            return;
        }

        $oDb = cRegistry::getDb();

        $timestamp = date('Y-m-d H:i:s');
        $idcatart = 0;

        $categoryId = cSecurity::toInteger($categoryId);
        $articleId = cSecurity::toInteger($articleId);
        $originalAction = $action;
        $action = $oDb->escape($action);

        if ($categoryId > 0 && $articleId > 0) {
            $oCatArtColl = new cApiCategoryArticleCollection();
            $oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($categoryId, $articleId);
            $idcatart = $oCatArt->get('idcatart');
        }

        $perm = cRegistry::getPerm();
        $oldAction = $action;
        $action = $perm->getIdForAction($action);

        if ($action != '') {
            $auth = cRegistry::getAuth();
            $oActionLogColl = new cApiActionlogCollection();
            $oActionLogColl->create($auth->auth['uid'], $clientId, $languageId, $action, $idcatart, $timestamp);
        } else {
            $frame = cRegistry::getFrame();
            $msg = 'cBackend: ' . $oldAction . ' is not in the actions table! ' . "\n"
                . 'Parameter: ' . print_r([
                    'idcat' => $categoryId, 'idart' => $articleId, 'client' => $clientId, 'lang' => $languageId,
                    'frame' => $frame, 'originalAction' => $originalAction, 'action' => $action
                ], true
                );
            cDebug::out($msg);
        }
    }

}
