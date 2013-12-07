<?php
/**
 * This file contains the frontend class for smarty wrapper plugin.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 * @version SVN Revision $Rev:$
 *
 * @author Andreas Dieter
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for Integration of smarty.
 *
 * @deprecated 2013-11-12 - Use the class cSmartyFrontend instead.
 * @package Plugin
 * @subpackage SmartyWrapper
 */
class Contenido_SmartyWrapper extends cSmartyFrontend {

    public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false) {
        cDeprecated("Use the class cSmartyFrontend instead.");
        parent::__construct($aCfg, $aClientCfg, $bSanityCheck);
    }

}
/**
 * Wrapper class for Integration of smarty.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 */
class cSmartyFrontend {

    /**
     * The smarty Object
     *
     * @var Smarty
     */
    protected static $oSmarty;

    /**
     * static flag to simulate singleton behaviour
     *
     * @var bool
     */
    public static $bSmartyInstanciated = false;

    /**
     * static default paths
     *
     * @var array
     */
    protected static $aDefaultPaths = array();

    /**
     * constructor
     *
     * @param array &$aCfg contenido cfg array
     * @param array &$aClientCfg contenido client cfg array of the specific
     *        client
     * @throws cException
     * @throws cInvalidArgumentException if the given configurations are not an
     *         array
     */
    public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false) {
        // check if already instanciated
        if (isset(self::$bSmartyInstanciated) && self::$bSmartyInstanciated) {
            throw new cException("cSmartyFrontend class is intended to be used as singleton. Do not instanciate multiple times.");
        }

        if (!is_array($aCfg)) {
            throw new cInvalidArgumentException(__CLASS__ . " " . __FUNCTION__ . " Parameter 1 invalid.");
        }

        if (!is_array($aClientCfg)) {
            throw new cInvalidArgumentException(__CLASS__ . " " . __FUNCTION__ . " Parameter 2 invalid.");
        }

        self::$oSmarty = new cSmartyWrapper();
        self::$aDefaultPaths = array(
            'template_dir' => $aClientCfg['module']['path'],
            'cache_dir' => $aClientCfg['cache']['path'] . 'templates_c',
            'compile_dir' => $aClientCfg['cache']['path'] . 'templates_c'
        );

        // check the template directory and create new one if it not exists
        if (!is_dir(self::$aDefaultPaths['compile_dir'])) {
            mkdir(self::$aDefaultPaths['compile_dir'], 0777);
        }

        // check if folders exist and rights ok if needed
        if ($bSanityCheck) {
            foreach (self::$aDefaultPaths as $key => $value) {
                if (!file_exists($value)) {
                    throw new cException(sprintf("Class %s Error: Folder %s does not exist. Please create.", __CLASS__, $value));
                }
                if ($key == 'cache' || $key == 'compile_dir') {
                    if (!is_writable($value)) {
                        throw new cException(sprintf("Class %s Error: Folder %s is not writable. Please check for sufficient rights.", __CLASS__, $value));
                    }
                }
            }
        }

        self::resetPaths();
        self::$bSmartyInstanciated = true;
    }

    /**
     * prevent users from cloning instance
     *
     * @throws cException if this function is called
     */
    public function __clone() {
        throw new cException("cSmartyFrontend class is intended to be used as singleton. Do not clone.");
    }

    /**
     * destructor
     * set cSmarty::bSmartyInstanciated to false
     */
    public function __destruct() {
        self::$bSmartyInstanciated = false;
    }

    /**
     * static function to provide the smart object
     *
     * @param boolean $bResetTemplate true if the template values shall all be
     *        resetted
     * @throws cException if singleton has not been instantiated yet
     * @return cSmartyWrapper
     */
    public static function getInstance($bResetTemplate = false) {
        if (!isset(self::$oSmarty)) {
            // @TODO find a smart way to instanciate smarty object on demand
            throw new cException("Smarty singleton not instantiated yet.");
        }
        if ($bResetTemplate) {
            self::$oSmarty = new cSmartyWrapper();
            self::resetPaths();
        }
        return self::$oSmarty;
    }

    /**
     * sets the default paths again
     */
    public static function resetPaths() {
        self::$oSmarty->setTemplateDir(self::$aDefaultPaths['template_dir']);
        self::$oSmarty->setCacheDir(self::$aDefaultPaths['cache_dir']);
        self::$oSmarty->setCompileDir(self::$aDefaultPaths['compile_dir']);
    }

}

?>