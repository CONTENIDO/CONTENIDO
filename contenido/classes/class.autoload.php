<?php

/**
 * This file contains the autoloader class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Implements autoload feature for a CONTENIDO project.
 *
 * Autoload for CONTENIDO is provided via a generated class map configuration file,
 * which is available inside the data/config/{environment}/ folder.
 * - data/config/{environment}/config.autoloader.php
 *
 * Autoload is extendable by adding a class map file inside the same folder, which could contain
 * further class map settings or could overwrite settings of the main class map file.
 * - data/config/{environment}/contenido/includes/config.autoloader.local.php
 *
 * You can also add additional class map configuration by using the function following functions:
 * - cAutoload::addClassmapConfig(array $config)
 * - cAutoload::addClassmapConfigFile(string $configFile)
 *
 * Read also docs/techref/backend/backend.autoloader.html to get involved in the CONTENIDO autoloader mechanism.
 *
 * @package    Core
 * @subpackage Backend
 */
class cAutoload
{

    /**
     * @var string Identifier for error if a class file could not be found.
     */
    public const ERROR_FILE_NOT_FOUND = 'file_not_found';

    /**
     * @var string Identifier for error if the class already exists.
     */
    public const ERROR_CLASS_EXISTS = 'class_exists';

    /**
     * @var ?string CONTENIDO root path. Path to the folder which contains the CONTENIDO installation.
     */
    private static $conRootPath = NULL;

    /**
     * @var ?array Array of interface/class names with related files to include
     */
    private static $includeFiles = NULL;

    /**
     * @var ?bool Flag containing initialized status
     */
    private static $isInitialized = NULL;

    /**
     * Array to store loaded classnames and the paths to the class files.
     * <pre>
     * $loadedClasses['classname'] = '/path/to/the/class.php';
     * </pre>
     *
     * @var array<string, string>
     */
    private static $loadedClasses = [];

    /**
     * Array to store invalid classnames and the paths to the class files.
     * <pre>
     * $errors[pos] = [
     *     'class' => classname,
     *     'file' => file,
     *     'error' => errorType
     * ];
     * </pre>
     *
     * @var array<int, array{class: string, file: string, error: string}>
     */
    private static $errors = [];

    /**
     * Initialization of CONTENIDO autoloader, it is to call at least once.
     *
     * Registers itself as a __autoload implementation, includes the class map file,
     * and if it exists, the user-defined class map file, containing the includes.
     *
     * @param array $cfg The CONTENIDO cfg array
     */
    public static function initialize(array $cfg)
    {
        if (self::$isInitialized) {
            return;
        }

        self::$isInitialized = true;
        self::$conRootPath = str_replace(
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
     * Since this autoloader is implemented for CONTENIDO, it doesn't support
     * loading class-files being located outside the CONTENIDO installation folder.
     *
     * @param array $config Associative class map array as follows:
     *      <pre>
     *      // Structure is:
     *      // "Classname" => "Path to class file from CONTENIDO installation folder"
     *      $classMapArray = [
     *          'myPluginsClass' => 'contenido/plugins/my_plugin/classes/class.myPluginClass.php',
     *          'myPluginsOtherClass' => 'contenido/plugins/my_plugin/classes/class.myPluginsOtherClass.php',
     *          'myCmsClass' => 'cms/includes/class.myCmsClass.php',
     *           // When defining a fully qualified class name with namespace in string
     *           // context, then use double backslash '\\' as the namespace separator.
     *          'myNamespace\\myPackage\\myClass' => '.../path/to/myNamespace/myPackage/myClass.php',
     *      ];
     *      </pre>
     */
    public static function addClassmapConfig(array $config)
    {
        $newConfig = self::_normalizeConfig($config);
        if (!is_array(self::$includeFiles)) {
            self::$includeFiles = [];
        }
        self::$includeFiles = array_merge(self::$includeFiles, $newConfig);
    }

    /**
     * Adding an autoloader class map configuration file.
     * NOTE:
     * Since this autoloader is implemented for CONTENIDO, it doesn't support
     * loading class-files being located outside the CONTENIDO installation folder.
     *
     * @param string $configFile Full path to the class map configuration file. The provided file
     *      must return a class map configuration array as follows:
     *      <pre>
     *      // Structure is:
     *      // "Classname" => "Path to class file from CONTENIDO installation folder"
     *      return [
     *          'myPluginsClass' => 'contenido/plugins/my_plugin/classes/class.myPluginClass.php',
     *          'myPluginsOtherClass' => 'contenido/plugins/my_plugin/classes/class.myPluginsOtherClass.php',
     *          'myCmsClass' => 'cms/includes/class.myCmsClass.php',
     *           // When defining a fully qualified class name with namespace in string
     *           // context, then use double backslash '\\' as the namespace separator.
     *          'myNamespace\\myPackage\\myClass' => '.../path/to/myNamespace/myPackage/myClass.php',
     *      ];
     *      </pre>
     */
    public static function addClassmapConfigFile(string $configFile)
    {
        if (is_file($configFile)) {
            $arr = include_once($configFile);
            if ($arr) {
                self::addClassmapConfig($arr);
            }
        }
    }

    /**
     * The main __autoload() implementation.
     * Tries to include the file of the passed classname.
     *
     * @param string $className The classname
     * @throws cBadMethodCallException If autoloader wasn't initialized before
     */
    public static function autoload(string $className)
    {
        if (self::$isInitialized !== true) {
            throw new cBadMethodCallException(
                'Autoloader has to be initialized by calling method initialize()'
            );
        }

        if (isset(self::$loadedClasses[$className])) {
            return;
        }

        $file = self::_getContenidoClassFile($className);
        if (is_null($file)) {
            return;
        }

        if ($file) {
            // load class file from a class map
            self::_loadFile($file);
        }

        self::$loadedClasses[$className] = str_replace(self::$conRootPath, '', $file);
    }

    /**
     * Checks if the passed filename is a file, which will be included by the autoloader.
     *
     * @param string $file Filename or Filename with a part of the path, e.g.
     *      - class.foobar.php
     *      - classes/class.foobar.php
     *      - contenido/classes/class.foobar.php
     */
    public static function isAutoloadable(string $file): bool
    {
        foreach (self::$includeFiles as $includeFile) {
            if (cString::findFirstPos($includeFile, $file) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the loaded classes.
     */
    public static function getLoadedClasses(): array
    {
        return self::$loadedClasses;
    }

    /**
     * Returns the error-list containing invalid classes.
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    /**
     * Returns the path to a CONTENIDO class file by processing the given classname.
     *
     * @param string $className
     * @return ?string String if validation was successful, otherwise null
     */
    private static function _getContenidoClassFile(string $className): ?string
    {
        $classNameLower = cString::toLowerCase($className);
        $file = isset(self::$includeFiles[$classNameLower])
            ? self::$conRootPath . self::$includeFiles[$classNameLower] : '';
        return self::_validateClassAndFile($className, $file);
    }

    /**
     * Validates the given classname and filename.
     *
     * @param string $classname
     * @param string $filename
     * @return ?string String if validation was successful, otherwise null
     */
    private static function _validateClassAndFile(string $classname, string $filename): ?string
    {
        if (class_exists($classname)) {
            self::$errors[] = [
                'class' => $classname,
                'file' => str_replace(self::$conRootPath, '', $filename),
                'error' => self::ERROR_CLASS_EXISTS
            ];
            return NULL;
        } elseif (!empty($filename) && !is_file($filename)) {
            self::$errors[] = [
                'class' => $classname,
                'file' => str_replace(self::$conRootPath, '', $filename),
                'error' => self::ERROR_FILE_NOT_FOUND
            ];
            return NULL;
        } else {
            return $filename;
        }
    }

    /**
     * Normalizes the provided configuration array by returning a new copy of it which contains
     * the keys in lowercase.
     * This prevents errors by trying to load class 'foobar' if the real class name is 'FooBar'.
     */
    private static function _normalizeConfig(array $config): array
    {
        $newConfig = [];
        foreach ($config as $name => $file) {
            $newConfig[cString::toLowerCase($name)] = $file;
        }
        return $newConfig;
    }

    /**
     * Loads the desired file by including it
     *
     * @param bool $beQuiet Flag to prevent thrown warnings/errors by using the `include_once` expression.
     */
    private static function _loadFile(string $filePathName, bool $beQuiet = false)
    {
        if ($beQuiet) {
            include_once $filePathName;
        } else {
            require_once $filePathName;
        }
    }

}
