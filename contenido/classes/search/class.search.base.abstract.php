<?php

/**
 * This file contains the base class for content search.
 *
 * @package Core
 * @subpackage Frontend_Search
 * @version SVN Revision $Rev:$
 *
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.encoding.php');

/**
 * Abstract base search class.
 * Provides general properties and functions
 * for child implementations.
 *
 * @author Murat Purc <murat@purc.de>
 *
 * @package Core
 * @subpackage Frontend_Search
 */
abstract class cSearchBaseAbstract {

    /**
     * CONTENIDO database object
     *
     * @var cDb
     */
    protected $oDB;

    /**
     * CONTENIDO configuration data
     *
     * @var array
     */
    protected $cfg;

    /**
     * Language id of a client
     *
     * @var int
     */
    protected $lang;

    /**
     * Client id
     *
     * @var int
     */
    protected $client;

    /**
     * Initialises some properties
     *
     * @param cDb $oDB
     *         Optional database instance
     * @param bool $bDebug
     *         Optional, flag to enable debugging (no longer needed)
     */
    protected function __construct($oDB = NULL, $bDebug = false) {
        global $cfg, $lang, $client;

        $this->cfg = $cfg;
        $this->lang = $lang;
        $this->client = $client;

        $this->bDebug = $bDebug;

        if ($oDB == NULL || !is_object($oDB)) {
            $this->db = cRegistry::getDb();
        } else {
            $this->db = $oDB;
        }
    }

    /**
     * Main debug function, prints dumps parameter if debugging is enabled
     *
     * @param string $msg
     *         Some text
     * @param mixed $var
     *         The variable to dump
     */
    protected function _debug($msg, $var) {
        $dump = $msg . ': ';
        if (is_array($var) || is_object($var)) {
            $dump .= print_r($var, true);
        } else {
            $dump .= $var;
        }
        cDebug::out($dump);
    }
}
