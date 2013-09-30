<?php
/**
 * This file contains the log file writer class.
 *
 * @package    Core
 * @subpackage Log
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the file writer class for the logging mechanism.
 *
 * @package    Core
 * @subpackage Log
 */
class cLogWriterFile extends cLogWriter {
    /**
     * @var    resource    Destination handle
     */
    protected $_handle = NULL;

    /**
     * Constructor of the writer instance.
     * @param    array    $options    Array with options for the writer instance (optional)
     */
    public function __construct($options = array()) {
        parent::__construct($options);

        $this->_createHandle();
    }

    /**
     * Checks destination and creates the handle for the write process.
     *
     * @throws cException if not destination is specified
     * @throws cFileNotFoundException if the destination file could not be read
     */
    protected function _createHandle() {
        $destination = $this->getOption('destination');
        if ($destination == '') {
            throw new cException('No destination was specified.');
        }

        if (($this->_handle = fopen($destination, 'a')) === false) {
            throw new cFileNotFoundException('Destination handle could not be created.');
        }
    }

    /**
     * Writes the content to file handle.
     *
     * @param    string    $message    Message to write
     * @param    int        $priority    Priority of the log entry
     * @return    boolean    State of the write process
     */
    public function write($message, $priority) {
        return (fwrite($this->_handle, $message) != false);
    }
}
