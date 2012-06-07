<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Wrapper class for Integration of smarty
 *
 * Requirements:
 *
 *
 * @package    Contenido Template classes
 * @version    1.3.0
 * @author     Andreas Dieter
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since
 *
 * {@internal
 *     created     2010-07-22
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class Contenido_SmartyWrapper {

    /**
     * The smarty Object
     * @access private
     */
    protected static $oSmarty;

    /**
     * static flag to simulate singleton behaviour
     * @access public static
     */
    public static $bSmartyInstanciated = false;

    /**
     * static default paths
     * @access private static
     */
    protected static $aDefaultPaths = array();

    /**
     * constructor
     * @param  array  &$aCfg        contenido cfg array
     * @param  array  &$aClientCfg  contenido client cfg array of the specific client
     */
     public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false) {
        // check if already instanciated
        if (isset(self::$bSmartyInstanciated) && self::$bSmartyInstanciated) {
            throw new Exception("Contenido_SmartyWrapper class is intended to be used as singleton. Do not instanciate multiple times.");
        }

        if (!is_array($aCfg)) {
            throw new Exception( __CLASS__ . " " . __FUNCTION__ . " Parameter 1 invalid.");
        }
        if (!is_array($aClientCfg)) {
            return;
            throw new Exception( __CLASS__ . " " . __FUNCTION__ . " Parameter 2 invalid.");
        }

        // Load smarty
        if (!defined('SMARTY_DIR')) {
            define('SMARTY_DIR' , $aCfg['path']['contenido'].'plugins/smarty/smarty_source/');
        }
        require_once(SMARTY_DIR . 'Smarty.class.php');
        self::$oSmarty = new Smarty();
        self::$aDefaultPaths = array(
            'template_dir' => $aClientCfg['module_path'],
            'cache_dir'    => $aClientCfg['cache_path'],
            'compile_dir'  => $aClientCfg['cache_path'] . 'templates_c'
        );

        // check the template directory and create new one if it not exists
        if (!is_dir(self::$aDefaultPaths['compile_dir'])) {
            mkdir(self::$aDefaultPaths['compile_dir'], 0777);
        }

        // check if folders exist and rights ok if needed
        if ($bSanityCheck) {
            foreach (self::$aDefaultPaths as $key => $value) {
                if (!file_exists($value)) {
                    throw new Exception(sprintf("Class %s Error: Folder %s does not exist. Please create.", __CLASS__, $value));
                }
                if ($key == 'cache' || $key == 'compile_dir') {
                    if (!is_writable($value)) {
                        throw new Exception(sprintf("Class %s Error: Folder %s is not writable. Please check for sufficient rights.", __CLASS__, $value));
                    }
                }
            }
        }

        self::$oSmarty->template_dir = self::$aDefaultPaths['template_dir'];
        self::$oSmarty->cache_dir    = self::$aDefaultPaths['cache_dir'];
        self::$oSmarty->compile_dir  = self::$aDefaultPaths['compile_dir'];

        // config_dir not needed yet
        // Technical Note:  It is not recommended to put this directory under the web server document root.
        // self::$oSmarty->config_dir = "/somewhere/";

        self::$bSmartyInstanciated = true;
    }

    /**
     * prevent users from cloning instance
     */
    public function __clone() {
        throw new Exception("Contenido_SmartyWrapper class is intended to be used as singleton. Do not clone.");
    }

    /**
     * destructor
     * set Contenido_SmartyWrapper::bSmartyInstanciated to false
     */
     public function __destruct() {
        self::$bSmartyInstanciated = false;
    }

    /**
     * static function to provide the smart object
     * @access public static
     * @param boolean bResetTemplate     true if the template values shall all be resetted
     * @return smarty
     */
    public static function getInstance($bResetTemplate = false) {
        if (!isset(self::$oSmarty)) {
            // @TODO  find a smart way to instanciate smarty object on demand
            throw new Exception("Smarty singleton not instanciated yet.");
        }
        if ($bResetTemplate) {
            self::$oSmarty = new Smarty();
            self::resetPaths();
        }
        return self::$oSmarty;
    }

    /**
     * sets the default paths again
     */
    public static function resetPaths() {
        self::$oSmarty->template_dir = self::$aDefaultPaths['template_dir'];
        self::$oSmarty->cache_dir    = self::$aDefaultPaths['cache_dir'];
        self::$oSmarty->compile_dir  = self::$aDefaultPaths['compile_dir'];
    }
}

?>