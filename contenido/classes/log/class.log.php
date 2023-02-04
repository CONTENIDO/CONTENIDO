<?php

/**
 * This file contains the log class.
 *
 * @package    Core
 * @subpackage Log
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the main functionalities for the logging in CONTENIDO.
 *
 * Examples:
 * <pre>
 * $writer = cLogWriter::factory('File', ['destination' => 'contenido.log']);
 * $log = new cLog($writer);
 *
 * $log->addPriority('CONTENIDO', 10);
 * $log->log('CONTENIDO Log Message.', 'CONTENIDO');
 * $log->contenido('Same log entry in short notation.');
 * $log->removePriority('CONTENIDO');
 *
 * $log->emerg('System down.');
 *
 * $log->log('Notice Log Message', cLog::NOTICE);
 *
 * $log->buffer('Buffered Log Message', cLog::WARN);
 * $log->commit();
 * </pre>
 *
 * // Magic methods of cLog, see class constants
 * @method void emerg(string $message)
 * @method void alert(string $message)
 * @method void crit(string $message)
 * @method void err(string $message)
 * @method void warn(string $message)
 * @method void notice(string $message)
 * @method void info(string $message)
 * @method void debug(string $message)
 *
 * @package    Core
 * @subpackage Log
 */
class cLog {

    /**
     * logging level
     *
     * @var int
     */
    const EMERG = 0;

    /**
     * logging level
     *
     * @var int
     */
    const ALERT = 1;

    /**
     * logging level
     *
     * @var int
     */
    const CRIT = 2;

    /**
     * logging level
     *
     * @var int
     */
    const ERR = 3;

    /**
     * logging level
     *
     * @var int
     */
    const WARN = 4;

    /**
     * logging level
     *
     * @var int
     */
    const NOTICE = 5;

    /**
     * logging level
     *
     * @var int
     */
    const INFO = 6;

    /**
     * logging level
     *
     * @var int
     */
    const DEBUG = 7;

    /**
     * Contains the local log writer instance.
     *
     * @var cLogWriter
     */
    protected $_writer;

    /**
     * Contains all shortcut handlers.
     *
     * @var array
     */
    protected $_shortcutHandlers = [];

    /**
     * Contains all available priorities.
     *
     * @var array
     */
    protected $_priorities = [];

    /**
     * Contains all default priorities.
     *
     * @var array
     */
    protected $_defaultPriorities = [];

    /**
     * Contains all buffered messages.
     *
     * @var array
     */
    protected $_buffer = [];

    /**
     * Constructor to create an instance of this class.
     *
     * The log format interface of cLog is capable of being extended by
     * subclasses. See the note about the log shortcuts below.
     *
     *
     * About Log Shortcuts
     * -------------------
     * Log shortcuts are placeholders which are replaced when a log
     * entry is created. Placeholders start with a percentage sign (%)
     * and contain one or more characters. Each placeholder is handled
     * by an own function which decides what to do.
     *
     * @param mixed $writer [optional]
     *                      Writer object (any subclass of cLogWriter), or false if
     *                      cLog should handle the writer creation
     *
     * @throws cInvalidArgumentException
     */
    public function __construct($writer = false) {
        $cfg = cRegistry::getConfig();
        $createWriter = false;

        if (!$writer) {
            $createWriter = true;
        } else if (!is_object($writer) || !($writer instanceof cLogWriter)) {
            cWarning(__FILE__, __LINE__, 'The passed class is not a subclass of cLogWriter. Creating new one.');
            $createWriter = true;
        }

        if ($createWriter) {
            $options = ['destination' => $cfg['path']['contenido_logs'] . 'data/contenido.log'];
            $writer = cLogWriter::factory('File', $options);
        }

        $this->setWriter($writer);
        $this->setShortcutHandler('%date', [$this, 'shDate']);
        $this->setShortcutHandler('%level', [$this, 'shLevel']);
        $this->setShortcutHandler('%message', [$this, 'shMessage']);

        $this->getWriter()->setOption('log_format', '[%date] [%level] %message', false);

        $reflection = new ReflectionClass($this);
        $this->_priorities = $this->_defaultPriorities = array_flip($reflection->getConstants());
    }

    /**
     * Returns the local writer instance.
     *
     * @return cLogWriter
     */
    public function getWriter() {
        return $this->_writer;
    }

    /**
     * Sets the local writer instance.
     *
     * @param cLogWriter $writer
     *         Writer instance
     */
    public function setWriter(cLogWriter $writer) {
        $this->_writer = $writer;
    }

    /**
     * Defines a custom shortcut handler.
     *
     * Each shortcut handler receives an array with the message and the
     * priority of the entry.
     *
     * @param string $shortcut
     *         Shortcut name
     * @param string|array $handler
     *         Name of the function to call
     * @throws cInvalidArgumentException
     *         if the given shortcut is empty or already in use or if the
     *         handler is not callable
     * @return bool
     *         True if setting was successful
     */
    public function setShortcutHandler($shortcut, $handler) {
        if ($shortcut == '') {
            throw new cInvalidArgumentException('The shortcut name must not be empty.');
        }

        if (cString::getPartOfString($shortcut, 0, 1) == '%') {
            $shortcut = cString::getPartOfString($shortcut, 1);
        }

        if (!is_callable($handler)) {
            throw new cInvalidArgumentException('The specified shortcut handler does not exist.');
        }

        if (array_key_exists($shortcut, $this->_shortcutHandlers)) {
            throw new cInvalidArgumentException('The shortcut ' . $shortcut . ' is already in use!');
        }

        $this->_shortcutHandlers[$shortcut] = $handler;

        return true;
    }

    /**
     * Unsets a specific shortcut handler.
     *
     * @param string $shortcut
     *         Name of the shortcut
     * @throws cInvalidArgumentException
     *         if the given shortcut handler does not exist
     * @return bool
     */
    public function unsetShortcutHandler($shortcut) {
        if (!in_array($shortcut, $this->_shortcutHandlers)) {
            throw new cInvalidArgumentException('The specified shortcut handler does not exist.');
        }

        unset($this->_shortcutHandlers[$shortcut]);
        return true;
    }

    /**
     * Buffers a log message for committing them on a later moment.
     *
     * @param string $message
     *         Message to buffer
     * @param mixed $priority [optional]
     *         Priority of the log entry (optional)
     */
    public function buffer($message, $priority = NULL) {
        $this->_buffer[] = [$message, $priority];
    }

    /**
     * Commits all buffered messages and empties the message buffer if
     * parameter is not false.
     *
     * @param bool $revoke [optional]
     *         Flag, whether the buffer is cleared or not (optional, default: true)
     * @return bool|void
     * @throws cInvalidArgumentException
     */
    public function commit($revoke = true) {
        if (count($this->_buffer) == 0) {
            cWarning(__FILE__, __LINE__, 'There are no buffered messages to commit.');
            return false;
        }

        foreach ($this->_buffer as $bufferInfo) {
            $this->log($bufferInfo[0], $bufferInfo[1]);
        }

        if ($revoke) {
            $this->revoke();
        }
    }

    /**
     * Empties the message buffer.
     */
    public function revoke() {
        $this->_buffer = [];
    }

    /**
     * Logs a message using the local writer instance.
     *
     * @param string $message
     *         Message to log
     * @param mixed $priority [optional]
     *         Priority of the log entry (optional)
     */
    public function log($message, $priority = NULL) {
        if ($priority && !is_int($priority) && in_array($priority, $this->_priorities)) {
            $priority = array_search($priority, $this->_priorities);
        }

        if ($priority === NULL || !array_key_exists($priority, $this->_priorities)) {
            $priority = $this->getWriter()->getOption('default_priority');
        }

        $logMessage = $this->getWriter()->getOption('log_format');
        $lineEnding = $this->getWriter()->getOption('line_ending');

        foreach ($this->_shortcutHandlers as $shortcut => $handler) {
            if (cString::getPartOfString($shortcut, 0, 1) != '%') {
                $shortcut = '%' . $shortcut;
            }

            $info = [
                'message' => $message,
                'priority' => $priority
            ];

            $value = call_user_func($handler, $info);

            $logMessage = str_replace($shortcut, $value, $logMessage);
        }

        $this->getWriter()->write($logMessage . $lineEnding, $priority);
    }

    /**
     * Adds a new priority to the log.
     *
     * @param string $name
     *         Name of the log priority
     * @param int $value
     *         Index value of the log priority
     * @throws cInvalidArgumentException
     *         if the given name is empty, already exists or the value already exists
     */
    public function addPriority($name, $value) {
        if ($name == '') {
            throw new cInvalidArgumentException('Priority name must not be empty.');
        }

        if (in_array($name, $this->_priorities)) {
            throw new cInvalidArgumentException('The given priority name already exists.');
        }

        if (array_key_exists($value, $this->_priorities)) {
            throw new cInvalidArgumentException('The priority value already exists.');
        }

        $this->_priorities[$value] = $name;
    }

    /**
     * Removes a priority from log.
     * Default properties can not be removed.
     *
     * @param string $name
     *         Name of the log priority to remove
     * @throws cInvalidArgumentException
     *         if the given name is empty, does not exist or is a default priority
     */
    public function removePriority($name) {
        if ($name == '') {
            throw new cInvalidArgumentException('Priority name must not be empty.');
        }

        if (!in_array($name, $this->_priorities)) {
            throw new cInvalidArgumentException('Priority name does not exist.');
        }

        if (in_array($name, $this->_defaultPriorities)) {
            throw new cInvalidArgumentException('Removing default priorities is not allowed.');
        }

        $priorityIndex = array_search($name, $this->_priorities);

        unset($this->_priorities[$priorityIndex]);
    }

    /**
     * Magic call method for direct priority named calls.
     *
     * @param string $method
     *         Name of the method
     * @param array $arguments
     *         Array with the method arguments
     * @throws cInvalidArgumentException
     *         if the given priority is not supported
     */
    public function __call($method, $arguments) {
        $priorityName = cString::toUpperCase($method);

        if (!in_array($priorityName, $this->_priorities)) {
            throw new cInvalidArgumentException('The given priority ' . $priorityName . ' is not supported.');
        }

        $priorityIndex = array_search($priorityName, $this->_priorities);

        $this->log($arguments[0], $priorityIndex);
    }

    /**
     * Shortcut Handler Date.
     * Returns the current date.
     *
     * @return string
     *     The current date
     */
    public function shDate() {
        return date('Y-m-d H:i:s');
    }

    /**
     * Shortcut Handler Level.
     * Returns the canonical name of the priority.
     * The canonical name is padded to 10 characters to achieve a better
     * formatting.
     *
     * @param array $info
     * @return string
     *         The canonical log level
     */
    public function shLevel($info) {
        $logLevel = $info['priority'];
        return str_pad($this->_priorities[$logLevel], 10, ' ', STR_PAD_BOTH);
    }

    /**
     * Shortcut Handler Message.
     * Returns the log message.
     *
     * @param array $info
     * @return string
     *         The log message
     */
    public function shMessage($info) {
        return $info['message'];
    }
}
