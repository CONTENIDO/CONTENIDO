<?php
/**
 * This file contains the maillog collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Mail log collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMailLogCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['mail_log'], 'idmail');
        $this->_setItemClass('cApiMailLog');
    }

    /**
     * Creates a new mail log entry with the given data.
     * @param string|array $from
     * @param string|array $to
     * @param string|array $replyTo
     * @param string|array $cc
     * @param string|array $bcc
     * @param string $subject
     * @param string $body
     * @param string $created timestamp!
     * @param string $charset
     * @param string $contentType
     * @return cApiMailLog
     */
    public function create($from, $to, $replyTo, $cc, $bcc, $subject, $body, $created, $charset, $contentType) {
        $item = $this->createNewItem();

        $item->set('from', json_encode($from));
        $item->set('to', json_encode($to));
        $item->set('reply_to', json_encode($replyTo));
        $item->set('cc', json_encode($cc));
        $item->set('bcc', json_encode($bcc));
        $item->set('subject', $subject);
        $item->set('body', $body);
        $date = date('Y-m-d H:i:s', $created);
        $item->set('created', $date, false);
        $idclient = cRegistry::getClientId();
        $item->set('idclient', $idclient);
        $idlang = cRegistry::getLanguageId();
        $item->set('idlang', $idlang);
        $item->set('charset', $charset);
        $item->set('content_type', $contentType);

        $item->store();

        return $item;
    }
}

/**
 * Mail log item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMailLog extends Item {

    /**
     * Constructor
     *
     * @param mixed $mId
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['mail_log'], 'idmail');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
