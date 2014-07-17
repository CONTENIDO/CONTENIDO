<?php
/**
 * This file contains the maillog success collection and item class.
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
 * Mail log success collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMailLogSuccessCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['mail_log_success'], 'idmailsuccess');
        $this->_setItemClass('cApiMailLogSuccess');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiMailLogCollection');
    }

    /**
     * Creates a new mail log success entry with the given data.
     * @FIXME  Should return the cApiMailLogSuccess item. All create() methods should return the created item, no special treatments!
     * @param int $idmail
     * @param array $recipient
     * @param bool $success
     * @param string $exception
     * @return bool
     */
    public function create($idmail, $recipient, $success, $exception) {
        $item = $this->createNewItem();

        $item->set('idmail', $idmail);
        $item->set('recipient', json_encode($recipient));
        $item->set('success', $success);
        $item->set('exception', $exception);

        $item->store();

        return true;
    }
}

/**
 * Mail log success item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMailLogSuccess extends Item {

    /**
     * Constructor
     *
     * @param mixed $mId
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['mail_log_success'], 'idmailsuccess');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
