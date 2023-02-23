<?php
/**
 * This file contains the autoloader class.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Implements autoload feature for a CONTENIDO project.
 *
 * Autoload for CONTENIDO is provided via a generated class map configuration
 * file, which is available inside data/config/{environment}/ folder.
 * - data/config/{environment}/config.autoloader.php
 *
 * Autoload is extendable by adding a class map file inside the same
 * folder, which could contain further class map settings or could overwrite
 * settings of main class map file.
 * - data/config/{environment}/contenido/includes/config.autoloader.local.php
 *
 * You can also add additional class map configuration by using function
 * following
 * functions:
 * - cAutoload::addClassmapConfig(array $config)
 * - cAutoload::addClassmapConfigFile(string $configFile)
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
     * <pre>
     * $_loadedClasses['classname'] = '/path/to/the/class.php';
     * </pre>
     *
     * @var array
     */
    private static $_loadedClasses = [];

    /**
     * Array to store invalid classnames and the paths to the class files.
     * <pre>
     * $_errors[pos] = [
     *     'class' => classname,
     *     'file' => file,
     *     'error' => errorType
     * ];
     * </pre>
     *
     * @var array
     */
    private static $_errors = [];

    /**
     * Initialization of CONTENIDO autoloader, is to call at least once.
     *
     * Registers itself as a __autoload implementation, includes the class
     * map file and if exists, the user defined class map file, containing
     * the includes.
     *
     * @param array $cfg
     *         The CONTENIDO cfg array
     */
    public static function initialize(array $cfg) {
        if (self::$_initialized) {
            return;
        }

        self::$_initialized = true;
        self::$_conRootPath = str_replace(
                '\\', '/',
                realpath($cfg['path']['contenido'] . '/../')
            ) . '/';

        spl_autoload_register([__CLASS__, 'autoload']);

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
     * load class-files being located outside the CONTENIDO installation folder.
     *
     * @param array $config
     *         Associative class map array as follows:
     *         <pre>
     *         // Structure is:
     *         // "Classname" => "Path to class file from CONTENIDO installation folder"
     *         $classMapArray = [
     *             'myPluginsClass' =>
     *                 'contenido/plugins/my_plugin/classes/class.myPluginClass.php',
     *             'myPluginsOtherClass' =>
     *                 'contenido/plugins/my_plugin/classes/class.myPluginsOtherClass.php',
     *             'myCmsClass' =>
     *                 'cms/includes/class.myCmsClass.php',
     *              // When defining a fully qualified class name with namespace in string
     *              // context, then use double backslash '\\' as the namespace separator.
     *             'myNamespace\\myPackage\\myClass' =>
     *                 '.../path/to/myNamespace/myPackage/myClass.php',
     *         ];
     *         </pre>
     */
    public static function addClassmapConfig(array $config) {
        $newConfig = self::_normalizeConfig($config);
        if (!is_array(self::$_includeFiles)) {
            self::$_includeFiles = [];
        }
        self::$_includeFiles = array_merge(self::$_includeFiles, $newConfig);
    }

    /**
     * Adding additional autoloader class map configuration file.
     * NOTE:
     * Since this autoloader is implemented for CONTENIDO, it doesn't support to
     * load class-files being located outside the CONTENIDO installation folder.
     *
     * @param string $configFile
     *         Full path to class map configuration file. The provided file
     *         must return a class map configuration array as follows:
     *         <pre>
     *         // Structure is:
     *         // "Classname" => "Path to class file from CONTENIDO installation folder"
     *         return [
     *             'myPluginsClass' =>
     *                 'contenido/plugins/my_plugin/classes/class.myPluginClass.php',
     *             'myPluginsOtherClass' =>
     *                 'contenido/plugins/my_plugin/classes/class.myPluginsOtherClass.php',
     *             'myCmsClass' =>
     *                 'cms/includes/class.myCmsClass.php',
     *              // When defining a fully qualified class name with namespace in string
     *              // context, then use double backslash '\\' as the namespace separator.
     *             'myNamespace\\myPackage\\myClass' =>
     *                 '.../path/to/myNamespace/myPackage/myClass.php',
     *         ];
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
     *
     * @throws cBadMethodCallException
     *         If autoloader wasn't initialized before
     */
    public static function autoload($className) {
        if (self::$_initialized !== true) {
            throw new cBadMethodCallException(
                'Autoloader has to be initialized by calling method initialize()'
            );
        }

        if (isset(self::$_loadedClasses[$className])) {
            return;
        }

        $file = self::_getContenidoClassFile($className);
        if (is_null($file)) {
            return;
        }

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
     *         Filename or Filename with a part of the path, e.g.
     *         - class.foobar.php
     *         - classes/class.foobar.php
     *         - contenido/classes/class.foobar.php
     * @return bool
     */
    public static function isAutoloadable($file) {
        foreach (self::$_includeFiles as $includeFile) {
            if (cString::findFirstPos($includeFile, $file) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the loaded classes.
     *
     * @return array
     */
    public static function getLoadedClasses() {
        return self::$_loadedClasses;
    }

    /**
     * Returns the error-list containing invalid classes.
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
     *         string if validation was successful, otherwise NULL
     */
    private static function _getContenidoClassFile($className) {
        $classNameLower = cString::toLowerCase($className);
        $file = isset(self::$_includeFiles[$classNameLower])
            ? self::$_conRootPath . self::$_includeFiles[$classNameLower] : '';
        return self::_validateClassAndFile($className, $file);
    }

    /**
     * Validates the given classname and filename.
     *
     * @param string $classname
     * @param string $filename
     * @return string|null
     *         string if validation was successful, otherwise NULL
     */
    private static function _validateClassAndFile($classname, $filename) {
        if (class_exists($classname)) {
            self::$_errors[] = [
                'class' => $classname,
                'file' => str_replace(self::$_conRootPath, '', $filename),
                'error' => self::ERROR_CLASS_EXISTS
            ];
            return NULL;
        } elseif (!empty($filename) && !is_file($filename)) {
            self::$_errors[] = [
                'class' => $classname,
                'file' => str_replace(self::$_conRootPath, '', $filename),
                'error' => self::ERROR_FILE_NOT_FOUND
            ];
            return NULL;
        } else {
            return $filename;
        }
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
        $newConfig = [];
        foreach ($config as $name => $file) {
            $newConfig[cString::toLowerCase($name)] = $file;
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
