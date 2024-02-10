<?php

/**
 * This file contains the frontend class for smarty wrapper plugin.
 *
 * @package    Plugin
 * @subpackage SmartyWrapper
 * @author     Andreas Dieter
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for Integration of smarty.
 *
 * @package    Plugin
 * @subpackage SmartyWrapper
 */
class cSmartyFrontend
{

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
    protected static $aDefaultPaths = [];

    /**
     * constructor
     *
     * @param array &$aCfg contenido cfg array
     * @param array &$aClientCfg contenido client cfg array of the specific
     *                           client
     * @param bool $bSanityCheck
     *
     * @throws cException
     * @throws cInvalidArgumentException if the given configurations are not an
     *         array
     */
    public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false)
    {
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
        self::$aDefaultPaths = [
            'template_dir' => $aClientCfg['module']['path'],
            'cache_dir' => $aClientCfg['cache']['path'] . 'templates_c',
            'compile_dir' => $aClientCfg['cache']['path'] . 'templates_c'
        ];

        // check the template directory and create new one if it not exists
        if (!is_dir(self::$aDefaultPaths['compile_dir'])) {
            mkdir(self::$aDefaultPaths['compile_dir'], cDirHandler::getDefaultPermissions());
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
    public function __clone()
    {
        throw new cException("cSmartyFrontend class is intended to be used as singleton. Do not clone.");
    }

    /**
     * destructor
     * set cSmarty::bSmartyInstanciated to false
     */
    public function __destruct()
    {
        self::$bSmartyInstanciated = false;
    }

    /**
     * static function to provide the smart object
     *
     * @param boolean $bResetTemplate true if the template values shall all be
     *        retested
     * @return cSmartyWrapper
     * @throws cException if singleton has not been instantiated yet
     */
    public static function getInstance($bResetTemplate = false)
    {
        if (!isset(self::$oSmarty)) {
            // @TODO find a smart way to instanciate smarty object on demand
            throw new cException("Smarty singleton not instantiated yet.");
        }
        if ($bResetTemplate) {
            self::$oSmarty = new cSmartyWrapper();
            self::resetPaths();
            self::registerDeprecatedPhpModifier();
        }
        return self::$oSmarty;
    }

    /**
     * sets the default paths again
     */
    public static function resetPaths()
    {
        self::$oSmarty->setTemplateDir(self::$aDefaultPaths['template_dir']);
        self::$oSmarty->setCacheDir(self::$aDefaultPaths['cache_dir']);
        self::$oSmarty->setCompileDir(self::$aDefaultPaths['compile_dir']);
    }

    /**
     * Function to register deprecated PHP functions as modifier.
     * This is deprecated as of Smarty 4.3.0, therefore we want to give the CONTENIDO 
     * community some time to adapt their templates.
     *
     * The list of registered modifier plugins is based on the CONTENIDO source 
     * (plugins and example client), this may differ on installations with custom and
     * modified templates.
     *
     * TODO This is a temporary solution, remove this later!
     *
     * @since CONTENIDO 4.10.2
     * @link https://github.com/CONTENIDO/CONTENIDO/issues/453
     * @return void
     * @throws SmartyException
     */
    private static function registerDeprecatedPhpModifier()
    {
        self::$oSmarty->registerPlugin('modifier', 'trim', 'trim');
        self::$oSmarty->registerPlugin('modifier', 'strlen', 'strlen');
        self::$oSmarty->registerPlugin('modifier', 'htmlentities', 'htmlentities');
        self::$oSmarty->registerPlugin('modifier', 'strtoupper', 'strtoupper');
        self::$oSmarty->registerPlugin('modifier', 'is_array', 'is_array');
        self::$oSmarty->registerPlugin('modifier', 'in_array', 'in_array');
        self::$oSmarty->registerPlugin('modifier', 'array_keys', 'array_keys');
    }

}
