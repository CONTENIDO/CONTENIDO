<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO In-Use classes
 *
 * Code is taken over from file contenido/classes/class.inuse.php in favor of
 * normalizing API.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-10-07
 *
 *   $Id: $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Class InUse
 * Class for In-Use management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class cApiInUseCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['inuse'], 'idinuse');
        $this->_setItemClass('cApiInUse');
    }

    /**
     * Marks a specific object as "in use". Note that items are released when the 
     * session is destroyed.
     *
     * Currently, the following types are defined and approved as internal CONTENIDO standard:
     * - article
     * - module
     * - layout
     * - template
     *
     * @param  string  $type  Specifies the type to mark.
     * @param  mixed   $objectid  Specifies the object ID
     * @param  string  $session  Specifies the session for which the "in use" mark is valid
     * @param  string  $user  Specifies the user which requested the in-use flag
     * @return cApiInUse|null
     */
    public function  markInUse($type, $objectid, $session, $user)
    {
        $type     = $this->escape($type);
        $objectid = $this->escape($objectid);
        $session  = $this->escape($session);
        $user     = $this->escape($user);

        $this->select("type='".$type."' AND objectid='".$objectid."'");

        $newitem = null;
        if (!$this->next()) {
            $newitem = parent::create();
            $newitem->set('type', $type);
            $newitem->set('objectid', $objectid);
            $newitem->set('session', $session);
            $newitem->set('userid', $user);
            $newitem->store();
        }
        return $newitem;
    }

    /**
     * Removes the "in use" mark from a specific object.
     *
     * @param  string  $type  Specifies the type to de-mark.
     * @param  mixed  $objectid  Specifies the object ID
     * @param  string  $session  Specifies the session for which the "in use" mark is valid
     */
    public function removeMark($type, $objectid, $session)
    {
        $type     = $this->escape($type);
        $objectid = $this->escape($objectid);
        $session  = $this->escape($session);

        $this->select("type='".$type."' AND objectid='".$objectid."' AND session='".$session."'");

        if ($obj = $this->next()) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes all marks for a specific type and session
     *
     * @param  string  $type  Specifies the type to de-mark.
     * @param  string  $session  Specifies the session for which the "in use" mark is valid
     */
    public function removeTypeMarks($type, $session)
    {
        $type    = $this->escape($type);
        $session = $this->escape($session);

        $this->select("type='".$type."' AND session='".$session."'");

        while ($obj = $this->next()) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes the mark for a specific item
     *
     * @param  string  $type  Specifies the type to de-mark.
     * @param  string  $itemid  Specifies the item
     */
    public function removeItemMarks($type, $itemid)
    {
        $type   = $this->escape($type);
        $itemid = $this->escape($itemid);

        $this->select("type='".$type."' AND objectid='".$itemid."'");

        while ($obj = $this->next()) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Removes all in-use marks for a specific session.
     *
     * @param  string  $session  Specifies the session for which the "in use" marks should be removed
     */
    public function removeSessionMarks($session)
    {
        $session = $this->escape($session);
        $this->select("session='".$session."'");

        while ($obj = $this->next()) {
            // Remove entry
            $this->delete($obj->get('idinuse'));
            unset($obj);
        }
    }

    /**
     * Checks if a specific item is marked
     *
     * @param  string  $type  Specifies the type to de-mark.
     * @param  mixed  $objectid  Specifies the object ID
     * @return cApiInUse|bool  Returns false if it's not in use or returns the object if it is.
     */
    public function checkMark($type, $objectid)
    {
        $type     = $this->escape($type);
        $objectid = $this->escape($objectid);

        $this->select("type='".$type."' AND objectid='".$objectid."'");

        if ($obj = $this->next()) {
            return $obj;
        } else {
            return false;
        }
    }

    /**
     * Checks and marks if not marked.
     *
     * Example: Check for "idmod", also return a lock message:
     * list($inUse, $message) = $col->checkAndMark("idmod", $idmod, true, i18n("Module is in use by %s (%s)"));
     *
     * Example 2: Check for "idmod", don't return a lock message
     * $inUse = $col->checkAndMark("idmod", $idmod);
     *
     * @param  string  $type  Specifies the type to de-mark.
     * @param  mixed   $objectid  Specifies the object ID
     * @param  bool    $returnWarning  If true, also returns an error message if in use
     * @param  string  $warningTemplate  String to fill with the template
     *                                  (%s as placeholder, first %s is the username, second is the real name)
     * @param  bool    $allowOverride  True if the user can override the lock
     * @param  string  $location  Value to append to the override lock button
     * @return bool|array  If returnWarning is false, returns a boolean value wether the object is locked. If
     *                     returnWarning is true, returns a 2 item array (boolean inUse, string errormessage).
     */
    public function checkAndMark($type, $objectid, $returnWarning = false, $warningTemplate = '', $allowOverride = false, $location = '')
    {
        global $sess, $auth, $notification, $area, $frame, $perm;

        if (($obj = $this->checkMark($type, $objectid)) === false) {
            $this->markInUse($type, $objectid, $sess->id, $auth->auth['uid']);
            $inUse = false;
            $disabled = '';
            $noti = '';
        } else {
            if ($returnWarning == true) {
                $vuser = new User();
                $vuser->loadUserByUserID($obj->get('userid'));
                $inUseUser = $vuser->getField('username');
                $inUseUserRealName = $vuser->getField('realname');

                $message = sprintf($warningTemplate, $inUseUser, $inUseUserRealName);

                if ($allowOverride == true && ($auth->auth['uid'] == $obj->get('userid') || $perm->have_perm())) {
                    $alt = i18n("Click here if you want to override the lock");

                    $link = $sess->url($location."&overridetype=".$type."&overrideid=".$objectid);

                    $warnmessage = i18n("Do you really want to override the lock?");
                    $script = "javascript:if (window.confirm('".$warnmessage."') == true) { window.location.href  = '".$link."';}";
                    $override = '<br><br><a alt="'.$alt.'" title="'.$alt.'" href="'.$script.'" class="standard">['.i18n("Override lock").']</a> <a href="javascript://" class="standard" onclick="elem = document.getElementById(\'contenido_notification\'); elem.style.display=\'none\'">['.i18n("Hide notification").']</a>';
                } else {
                    $override = '';
                }

                if (!is_object($notification)) {
                    $notification = new Contenido_Notification();
                }

                $noti = $notification->messageBox('warning', $message.$override, 0);
                $inUse = true;
            }
        }

        if ($returnWarning == true) {
            return (array($inUse, $noti));
        } else {
            return $inUse;
        }
    }
}


/**
 * Class cApiInUse
 * Class for a single in-use item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class cApiInUse extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['inuse'], 'idinuse');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}


################################################################################
# Old versions of inuse item collection and inuse item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in 
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * In use collection
 * @deprecated  [2011-10-06] Use cApiInUseCollection instead of this class.
 */
class InUseCollection extends cApiInUseCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function InUseCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }
}


/**
 * Single in use item
 * @deprecated  [2011-10-06] Use cApiInUse instead of this class.
 */
class InUseItem extends cApiInUse
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function InUseItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>