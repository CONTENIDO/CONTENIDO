<?php

/**
 * This file contains the log file writer class.
 *
 * @package    Core
 * @subpackage Log
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the file writer class for the logging mechanism.
 *
 * @package    Core
 * @subpackage Log
 */
class cLogWriterFile extends cLogWriter
{

    /**
     * @var resource Destination handle.
     */
    protected $_handle;

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $options Array with options for the writer instance (optional)
     * @throws cException|cFileNotFoundException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->_createHandle();
    }

    /**
     * Checks destination and creates the handle for the write process.
     *
     * @throws cException if not destination is specified
     * @throws cFileNotFoundException if the destination file could not be read
     */
    protected function _createHandle()
    {
        $destination = $this->getOption('destination');
        if (empty($destination)) {
            throw new cException('No destination was specified.');
        }

        if (($this->_handle = fopen($destination, 'a')) === false) {
            throw new cFileNotFoundException('Destination handle could not be created.');
        }
    }

    /**
     * Writes the content to file handle.
     *
     * @inheritDoc
     */
    public function write(string $message, int $priority): bool
    {
        return fwrite($this->_handle, $message) != false;
    }
}
