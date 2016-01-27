<?php
/**
 * This file contains the autoloader class.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Implements autoload feature for a CONTENIDO project.
 *
 * Autoloading for CONTENIDO is provided via a generated class map configuration
 * file, which is available inside data/config/{environment}/ folder.
 * - data/config/{environment}/config.autoloader.php
 *
 * Autoloading is extendable by adding a additional class map file inside the
 * same
 * folder, which could contain further class map settings or could overwrite
 * settings of main class map file.
 * - data/config/{environment}/contenido/includes/config.autoloader.local.php
 *
 * You can also add additional class map configuration by using function
 * following
 * functions:
 * - cAutoload::addClassmapConfig(array $config)
 * - cAutoload::addClassmapConfigFile($configFile)
 *
 * Read also docs/techref/backend/backend.autoloader.html to get involved in
 * CONTENIDO autoloader mechanism.
 *
 * @package Core
 * @subpackage Backend
 */
class cAutoload {

    /**
     * Identifier for error if class file could not be found.
     *
     * @var string
     */
    const ERROR_FILE_NOT_FOUND = 'file_not_found';

    /**
     * Identifier for error if class already exists.
     *
     * @var string
     */
    const ERROR_CLASS_EXISTS = 'class_exists';

    /**
     * CONTENIDO root path.
     * Path to the folder which contains the CONTENIDO installation.
     *
     * @var string
     */
    private static $_conRootPath = NULL;

    /**
     * Array of interface/class names with related files to include
     *
     * @var array
     */
    private static $_includeFiles = NULL;

    /**
     * Flag containing initialized status
     *
     * @var bool
     */
    private static $_initialized = NULL;

    /**
     * Array to store loaded classnames and the paths to the class files.
     * $_loadedClasses['classname'] = '/path/to/the/class.php';
     *
     * @var array
     */
    private static $_loadedClasses = array();

    /**
     * Array to store invalid classnames and the paths to the class files.
     * $_errors[pos] = array('class' => classname, 'file' => file, 'error' =>
     * errorType);
     *
     * @var array
     */
    private static $_errors = array();

    /**
     * Initialization of CONTENIDO autoloader, is to call at least once.
     *
     * Registers itself as a __autoload implementation, includes the class map
     * file,
     * and if exists, the user defined class map file, containing the includes.
     *
     * @param array $cfg
     *         The CONTENIDO cfg array
     */
    public static function initialize(array $cfg) {
        if (self::$_initialized == true) {
            return;
        }

        self::$_initialized = true;
        self::$_conRootPath = str_replace('\\', '/', realpath($cfg['path']['contenido'] . '/../')) . '/';

        spl_autoload_register(array(
            __CLASS__,
            'autoload'
        ));

        // load n' store autoloader class map file
        $file = $cfg['path']['contenido_config'] . 'config.autoloader.php';
        $arr = include_once($file);
        if ($arr) {
            self::addClassmapConfig($arr);
        }

        // load n' store additional autoloader class map file, if exists
        $file = $cfg['path']['contenido_config'] . 'config.autoloader.local.php';
        if (is_file($file)) {
            self::addClassmapConfigFile($file);
        }
    }

    /**
     * Adding additional autoloader class map configuration.
     * NOTE:
     * Since this autoloader is implemented for CONTENIDO, it doesn't support to
     * load classfiles being located outside of the CONTENIDO installation
     * folder.
     *
     * @param array $config
     *         Assoziative class map array as follows:
     *         <pre>
     *         // Structure is: "Classname" => "Path to classfile from CONTENIDO
     *         installation folder"
     *         $config = array(
     *         'myPluginsClass' =>
     *         'contenido/plugins/myplugin/classes/class.myPluginClass.php',
     *         'myPluginsOtherClass' =>
     *             'contenido/plugins/myplugin/classes/class.myPluginsOtherClass.php',
     *         );
     *         </pre>
     */
    public static function addClassmapConfig(array $config) {
        $newConfig = self::_normalizeConfig($config);
        if (!is_array(self::$_includeFiles)) {
            self::$_includeFiles = array();
        }
        self::$_includeFiles = array_merge(self::$_includeFiles, $newConfig);
    }

    /**
     * Adding additional autoloader class map configuration file.
     * NOTE:
     * Since this autoloader is implemented for CONTENIDO, it doesn't support to
     * load classfiles being located outside of the CONTENIDO installation
     * folder.
     *
     * @param string $configFile
     *         Full path to class map configuration file.
     *         The provided file must return a class map configuration array as
     *         follows:
     *         <pre>
     *         // Structure is: "Classname" => "Path to classfile from CONTENIDO
     *         installation folder"
     *         return array(
     *         'myPluginsClass' =>
     *         'contenido/plugins/myplugin/classes/class.myPluginClass.php',
     *         'myPluginsOtherClass' =>
     *             'contenido/plugins/myplugin/classes/class.myPluginsOtherClass.php',
     *         'myCmsClass' => 'cms/includes/class.myCmsClass.php',
     *         );
     *         </pre>
     */
    public static function addClassmapConfigFile($configFile) {
        if (is_file($configFile)) {
            $arr = include_once($configFile);
            if ($arr) {
                self::addClassmapConfig($arr);
            }
        }
    }

    /**
     * The main __autoload() implementation.
     * Tries to include the file of passed classname.
     *
     * @param string $className
     *         The classname
     * @throws cBadMethodCallException
     *         If autoloader wasn't initialized before
     */
    public static function autoload($className) {
        if (self::$_initialized !== true) {
            throw new cBadMethodCallException("Autoloader has to be initialized by calling method initialize()");
        }

        if (isset(self::$_loadedClasses[$className])) {
            return;
        }

        $file = self::_getContenidoClassFile($className);

        if ($file) {
            // load class file from class map
            self::_loadFile($file);
        }

        self::$_loadedClasses[$className] = str_replace(self::$_conRootPath, '', $file);
    }

    /**
     * Checks, if passed filename is a file, which will be included by the
     * autoloader.
     *
     * @param string $file
     *         Filename or Filename with a part of the path, e. g.
     *         - class.foobar.php
     *         - classes/class.foobar.php
     *         - contenido/classes/class.foobar.php
     * @return bool
     */
    public static function isAutoloadable($file) {
        foreach (self::$_includeFiles as $className => $includeFile) {
            if (strpos($includeFile, $file) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the loaded classes (@see cAutoload::$_loadedClasses)
     *
     * @return array
     */
    public static function getLoadedClasses() {
        return self::$_loadedClasses;
    }

    /**
     * Returns the errorlist containing invalid classes (@see
     * cAutoload::$_errors)
     *
     * @return array
     */
    public static function getErrors() {
        return self::$_errors;
    }

    /**
     * Returns the path to a CONTENIDO class file by processing the given
     * classname
     *
     * @param string $className
     * @return string|null
     *         string if validation was successfull, otherwise NULL
     */
    private static function _getContenidoClassFile($className) {
        $classNameLower = strtolower($className);
        $file = isset(self::$_includeFiles[$classNameLower]) ? self::$_conRootPath . self::$_includeFiles[$classNameLower] : NULL;
        return self::_validateClassAndFile($className, $file);
    }

    /**
     * Validates the given class and the file
     *
     * @param string $className
     * @param string $filePathName
     * @return string|null
     *         string if validation was successfull, otherwise NULL
     */
    private static function _validateClassAndFile($className, $filePathName) {
        if (class_exists($className)) {
            self::$_errors[] = array(
                'class' => $className,
                'file' => str_replace(self::$_conRootPath, '', $filePathName),
                'error' => self::ERROR_CLASS_EXISTS
            );
            return NULL;
        } elseif (!is_file($filePathName)) {
            self::$_errors[] = array(
                'class' => $className,
                'file' => str_replace(self::$_conRootPath, '', $filePathName),
                'error' => self::ERROR_FILE_NOT_FOUND
            );
            return NULL;
        }

        return $filePathName;
    }

    /**
     * Normalizes the passed configuration array by returning a new copy of it
     * which contains the keys in lowercase.
     * This prevents errors by trying to load class 'foobar' if the real class
     * name is 'FooBar'.
     *
     * @param array $config
     * @return array
     */
    private static function _normalizeConfig(array $config) {
        $newConfig = array();
        foreach ($config as $name => $file) {
            $newConfig[strtolower($name)] = $file;
        }
        return $newConfig;
    }

    /**
     * Loads the desired file by invoking require_once method
     *
     * @param string $filePathName
     * @param bool $beQuiet [optional]
     *         Flag to prevent thrown warnings/errors by using the error control
     *         operator @
     */
    private static function _loadFile($filePathName, $beQuiet = false) {
        if ($beQuiet) {
            @require_once($filePathName);
        } else {
            require_once($filePathName);
        }
    }
}
