<?php

/**
 * This file contains the generic db class.
 *
 * @package    Core
 * @subpackage GenericDB
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cGenericDb.
 * Handles the generic execution of callbacks.
 *
 * @package    Core
 * @subpackage GenericDB
 */
class cGenericDb
{

    /**
     * Callbacks are executed before a item is created.
     * Expected parameters for callback: none
     *
     * @var int
     */
    const CREATE_BEFORE = 10;

    /**
     * Callbacks are executed if item could not be created.
     * Expected parameters for callback: none
     *
     * @var int
     */
    const CREATE_FAILURE = 11;

    /**
     * Callbacks are executed if item could be created successfully.
     * Expected parameters for callback: ID of created item
     *
     * @var int
     */
    const CREATE_SUCCESS = 12;

    /**
     * Callbacks are executed before store process is executed.
     * Expected parameters for callback: Item instance
     *
     * @var int
     */
    const STORE_BEFORE = 20;

    /**
     * Callbacks are executed if store process failed.
     * This is also likely to happen if query would not change anything in
     * database!
     * Expected parameters for callback: Item instance
     *
     * @var int
     */
    const STORE_FAILURE = 21;

    /**
     * Callbacks are executed if store process saved the values in the database.
     * Expected parameters for callback: Item instance
     *
     * @var int
     */
    const STORE_SUCCESS = 22;

    /**
     * Callbacks are executed before deleting an item.
     * Expected parameters for callback: ID of them item to delete
     *
     * @var int
     */
    const DELETE_BEFORE = 30;

    /**
     * Callbacks are executed if deletion of an item fails.
     * Expected parameters for callback: ID of them item to delete
     *
     * @var int
     */
    const DELETE_FAILURE = 31;

    /**
     * Callbacks are executed if item was deleted successfully.
     * Expected parameters for callback: ID of them item to delete
     *
     * @var int
     */
    const DELETE_SUCCESS = 32;

    /**
     * Callback stack.
     *
     * @var array
     */
    private static $_callbacks = [];

    /**
     * Registers a new callback.
     *
     * Example:
     * cGenericDb::register(cGenericDb::CREATE_SUCCESS, 'itemCreateHandler', 'cApiArticle');
     * cGenericDb::register(cGenericDb::CREATE_SUCCESS, ['cCallbackHandler', 'executeCreateHandle'], 'cApiArticle');
     *
     * @param string $event
     *         Callback event, must be a valid value of a cGenericDb event constant
     * @param mixed $callback
     *         Callback to register
     * @param mixed $class
     *         Class name for registering callback (can be string of array with
     *         names of the concrete Item classes)
     * @throws cInvalidArgumentException
     *         if event or class are not set or the callback is not callable
     */
    public static function register($event, $callback, $class)
    {
        if (isset($event) === false) {
            throw new cInvalidArgumentException("No callback event for execution was given");
        }

        if (is_callable($callback) === false) {
            throw new cInvalidArgumentException("Given callback is not callable.");
        }

        if (isset($class) === false) {
            throw new cInvalidArgumentException("No class for registering callback was given.");
        }

        if (is_array($class)) {
            foreach ($class as $className) {
                self::$_callbacks[$className][$event][] = $callback;
            }
        } else {
            self::$_callbacks[$class][$event][] = $callback;
        }
    }

    /**
     * Unregisters all callbacks for a specific event in a class.
     *
     * Example:
     * cGenericDb::unregister(cGenericDb::CREATE_SUCCESS, 'cApiArticle');
     *
     * @param string $event
     *         Callback event, must be a valid value of a cGenericDb event constant
     * @param mixed $class
     *         Class name for unregistering callback (can be string of array
     *         with names of the concrete Item classes)
     * @throws cInvalidArgumentException
     *         if the event or the class are not set
     */
    public static function unregister($event, $class)
    {
        if (isset($event) === false) {
            throw new cInvalidArgumentException("No callback event for execution was given");
        }

        if (isset($class) === false) {
            throw new cInvalidArgumentException("No class for unregistering callbacks was given.");
        }

        if (is_array($class)) {
            foreach ($class as $className) {
                unset(self::$_callbacks[$className][$event]);
            }
        } else {
            unset(self::$_callbacks[$class][$event]);
        }
    }

    /**
     * Executes all callbacks for a specific event in a class.
     *
     * @param string $event
     *         Callback event, must be a valid value of a cGenericDb event constant
     * @param string $class
     *         Class name for executing callback
     * @param array $arguments [optional]
     *         Arguments to pass to the callback function
     * @throws cInvalidArgumentException
     *         if the event or class is not set
     */
    protected final function _executeCallbacks($event, $class, $arguments = [])
    {
        if (isset($event) === false) {
            throw new cInvalidArgumentException("No callback event for execution was given");
        }

        if (isset($class) === false) {
            throw new cInvalidArgumentException("No class for executing callbacks was given.");
        }

        if (!isset(self::$_callbacks[$class])) {
            return;
        }

        if (!isset(self::$_callbacks[$class][$event])) {
            return;
        }

        foreach (self::$_callbacks[$class][$event] as $callback) {
            call_user_func_array($callback, $arguments);
        }
    }
}
