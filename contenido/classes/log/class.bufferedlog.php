<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Buffered Log facility
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2004-09-28
 *
 *   $Id$
 * }}
 *
 */

 /** Examples **

  Buffered logging

  $log = new cBufferedLog;
  $log->buffer("this is a log message");
  $log->buffer("another log message");
  $log->commit();

  The commit call commits all messages on the stack.

  $log = new cBufferedLog;
  $log->buffer("this is a log message");
  $log->buffer("another log message");
  $log->revoke();

  The revoke call revokes (Discards) all messages on the stack.
*/

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cBufferedLog extends cLog
{
    /**
     * @var array Contains all buffered messages to be written
     */
    protected $_aMessages;

    /**
     * Creates a new instance of the CONTENIDO BufferedLog mechanism.
     *
     * cBufferedLog is a logging facility which uses cLog to do its logging,
     * and features buffered logging
     *
     * @param  object  oLogger  The object to use for logging, or false if a new one should be created.
     */
    public function __construct($oLogger = false)
    {
        $this->_aMessages = array();

        parent::__construct($oLogger);
    }

    /** @deprecated  [2012-05-25] Old constructor function for downwards compatibility */
    public function cBufferedLog($oLogger = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($oLogger);
    }

    /**
     * buffer: Puts a log message on the buffering stack
     *
     * @param sMessage     string     Message to log
     * @param bPriority integer    PEAR Loglevel (or default if null / omitted)
     *
     * @return none
     */
    public function buffer($sMessage, $iPriority = null)
    {
        array_push($this->_aMessages, array($sMessage, $iPriority));
    }

    /**
     * commit: Commits all messages which are queued on the stack
     */
    public function commit()
    {
        foreach ($this->_aMessages as $aMessage) {
            list($sMessage, $iPriority) = $aMessage;
            $this->log($sMessage, $iPriority);
        }
    }

    /**
     * revoke: Revoke (discards) all messages which are queued on the stack
     */
    public function revoke()
    {
        $this->_aMessages = array();
    }
}

?>