<?php

/**
 * This file contains the base class for content search.
 *
 * @package    Core
 * @subpackage Frontend_Search
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.encoding.php');

/**
 * Abstract base search class.
 *
 * Provides general properties and functions for child implementations.
 * @author     Murat Purc <murat@purc.de>
 * @package    Core
 * @subpackage Frontend_Search
 */
abstract class cSearchBaseAbstract
{

    /**
     * CONTENIDO database object.
     *
     * @var cDb
     */
    protected $oDB;

    /**
     * CONTENIDO configuration data.
     *
     * @var array
     */
    protected $cfg;

    /**
     * Language id of a client.
     *
     * @var int
     */
    protected $lang;

    /**
     * Client id.
     *
     * @var int
     */
    protected $client;

    /**
     * Database instance.
     *
     * @var cDb
     */
    protected $db;

    /**
     * @deprecated [2023-02-13] Since 4.10.2, debug flag is no longer needed since 05/2015.
     */
    protected $bDebug;

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises some properties.
     *
     * @param cDb $oDB [optional]
     *         CONTENIDO database object
     * @param bool $bDebug [optional]
     *         Optional, flag to enable debugging (no longer needed, deprecated since 4.10.2)
     */
    protected function __construct($oDB = NULL, $bDebug = false)
    {
        $this->cfg = cRegistry::getConfig();
        $this->lang = cSecurity::toInteger(cRegistry::getLanguageId());
        $this->client = cSecurity::toInteger(cRegistry::getClientId());

        $this->bDebug = $bDebug;

        if ($oDB == NULL || !is_object($oDB)) {
            $this->db = cRegistry::getDb();
        } else {
            $this->db = $oDB;
        }
    }

    /**
     * Main debug function, prints dumps parameter if debugging is
     * enabled.
     *
     * @param string $msg
     *         Some text
     * @param mixed $var
     *         The variable to dump
     *
     * @throws cInvalidArgumentException
     */
    protected function _debug($msg, $var)
    {
        $dump = $msg . ': ';
        if (is_array($var) || is_object($var)) {
            $dump .= print_r($var, true);
        } else {
            $dump .= $var;
        }
        cDebug::out($dump);
    }

}
