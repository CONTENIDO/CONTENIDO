<?php

/**
 * This file contains the inuse collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class InUse Class for In-Use management
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiInUseCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['inuse'], 'idinuse');
        $this->_setItemClass('cApiInUse');
    }

    /**
     * Marks a specific object as "in use". Note that items are released when
     * the session is destroyed. Currently, the following types are defined and
     * approved as internal CONTENIDO standard: - article - module - layout -
     * template
     *
     * @param string $type
     *         Specifies the type to mark.
     * @param mixed  $objectid
     *         Specifies the object ID
     * @param string $session
     *         Specifies the session for which the "in use" mark is valid
     * @param string $user
     *         Specifies the user which requested the in-use flag
     *
     * @return cApiInUse|NULL
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function markInUse($type, $objectid, $session, $user) {
        $type = $type;
        $objectid = $objectid;
        $session = $session;
        $user = $user;

        $this->select("type='" . $this->escape($type) . "' AND objectid='" . $this->escape($objectid) . "'");

        $newitem = NULL;
        if (!$this->next()) {
            $newitem = $this->createNewItem();
            $newitem->set('type', $type);
            $newitem->set('objectid', $objectid);
            $newitem->set('session', $session);
            $newitem->set('userid', $user);
            $newitem->set('timestamp', time());
            $newitem->store();
        }
        return $newitem;
    }

    /**
     * Removes the "in use" mark from a specific object.
     *
     * @param string $type
     *         Specifies the type to de-mark.
     * @param mixed  $objectid
     *         Specifies the object ID
     * @param string $session
     *         Specifies the session for which the "in use" mark is valid
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function removeMark($type, $objectid, $session) {
        $type = $this->escape($type);
        $objectid = $this->escape($objectid);
        $session = $this->escape($session);

        $this->select("type='" . $type . "' AND objectid='" . $objectid . "' AND session='" . $session . "'");

        if (($obj = $this->next()) !== false) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes all marks for a specific type and session
     *
     * @param string $type
     *         Specifies the type to de-mark.
     * @param string $session
     *         Specifies the session for which the "in use" mark is valid
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function removeTypeMarks($type, $session) {
        $type = $this->escape($type);
        $session = $this->escape($session);

        $this->select("type='" . $type . "' AND session='" . $session . "'");

        while (($obj = $this->next()) !== false) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes the mark for a specific item
     *
     * @param string $type
     *         Specifies the type to de-mark.
     * @param string $itemid
     *         Specifies the item
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function removeItemMarks($type, $itemid) {
        $type = $this->escape($type);
        $itemid = $this->escape($itemid);

        $this->select("type='" . $type . "' AND objectid='" . $itemid . "'");

        while (($obj = $this->next()) !== false) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes all in-use marks for a specific userId
     *
     * @param string $userId
     *         Specifies the user
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function removeUserMarks($userId) {
        $userId = $this->escape($userId);
        $this->select("userid='" . $userId . "'");

        while (($obj = $this->next()) !== false) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes all inuse entries which are older than the inuse timeout
     *
     * @throws cDbException
     * @throws cException
     */
    public function removeOldMarks() {
        $cfg = cRegistry::getConfig();
        $expire = time() - $cfg['inuse']['lifetime'];

        $this->select("timestamp < " . $expire);

        while (($obj = $this->next()) !== false) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes all in-use marks for a specific session.
     *
     * @param string $session
     *         Specifies the session for which the "in use" marks should be removed
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function removeSessionMarks($session) {
        $session = $this->escape($session);
        $this->select("session='" . $session . "'");

        while (($obj = $this->next()) !== false) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Checks if a specific item is marked
     *
     * @param string $type
     *         Specifies the type to de-mark.
     * @param mixed  $objectid
     *         Specifies the object ID
     * @return cApiInUse bool
     *         false if it's not in use or returns the object if it is.
     * @throws cDbException
     * @throws cException
     */
    public function checkMark($type, $objectid) {
        $type = $this->escape($type);
        $objectid = $this->escape($objectid);

        $this->select("type='" . $type . "' AND objectid='" . $objectid . "'");

        if (($obj = $this->next()) !== false) {
            return $obj;
        } else {
            return false;
        }
    }

    /**
     * Checks and marks if not marked. Example: Check for "idmod", also return a
     * lock message: list($inUse, $message) = $col->checkAndMark("idmod",
     * $idmod, true, i18n("Module is in use by %s (%s)")); Example 2: Check for
     * "idmod", don't return a lock message $inUse = $col->checkAndMark("idmod",
     * $idmod);
     *
     * @param string $type
     *                                Specifies the type to de-mark.
     * @param mixed  $objectid
     *                                Specifies the object ID
     * @param bool   $returnWarning   [optional]
     *                                If true, also returns an error message if in use
     * @param string $warningTemplate [optional]
     *                                String to fill with the template (%s as placeholder, first %s is
     *                                the username, second is the real name)
     * @param bool   $allowOverride   [optional]
     *                                True if the user can override the lock
     * @param string $location        [optional]
     *                                Value to append to the override lock button
     * @return bool array
     *                                returnWarning is false, returns a bool value wether the object
     *                                is locked. If returnWarning is true, returns a 2-item array
     *                                (bool inUse, string errormessage).
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function checkAndMark($type, $objectid, $returnWarning = false, $warningTemplate = '', $allowOverride = false, $location = '') {
        global $sess, $auth, $notification, $area, $frame, $perm;

        if ((($obj = $this->checkMark($type, $objectid)) === false) || ($auth->auth['uid'] == $obj->get('userid'))) {
            $this->markInUse($type, $objectid, $sess->id, $auth->auth['uid']);
            $inUse = false;
            $disabled = '';
            $noti = '';
        } else {
            if ($returnWarning == true) {
                $vuser = new cApiUser($obj->get('userid'));
                $inUseUser = $vuser->getField('username');
                $inUseUserRealName = $vuser->getField('realname');

                $message = sprintf($warningTemplate, $inUseUser, $inUseUserRealName);

                if ($allowOverride == true && ($auth->auth['uid'] == $obj->get('userid') || $perm->have_perm())) {
                    $alt = i18n("Click here if you want to override the lock");

                    $link = $sess->url($location . "&overridetype=" . $type . "&overrideid=" . $objectid);

                    $warnmessage = i18n("Do you really want to override the lock?");
                    $script = "javascript:if (window.confirm('" . $warnmessage . "') == true) { window.location.href  = '" . $link . "';}";
                    $override = '<br><br><a alt="' . $alt . '" title="' . $alt . '" href="' . $script . '" class="standard">[' . i18n("Override lock") . ']</a> <a href="javascript://" class="standard" onclick="elem = document.getElementById(\'contenido_notification\'); elem.style.display=\'none\'">[' . i18n("Hide notification") . ']</a>';
                } else {
                    $override = '';
                }

                if (!is_object($notification)) {
                    $notification = new cGuiNotification();
                }

                $noti = $notification->returnMessageBox('warning', $message . $override, 0);
                $inUse = true;
            }
        }

        if ($returnWarning == true) {
            return array($inUse, $noti);
        } else {
            return $inUse;
        }
    }

}

/**
 * Class cApiInUse Class for a single in-use item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiInUse extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *                   
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['inuse'], 'idinuse');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
