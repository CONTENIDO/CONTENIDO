<?php
/**
 * This file contains the abstract frontend logic class.
 *
 * @package    Plugin
 * @subpackage FrontendLogic
 * @version    SVN Revision $Rev:$
 *
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * FrontendLogic: This is the base class for all frontend related logic.
 *
 * Basically, the class FrontendLogic is the base class for all your objects in
 * the frontend. Your child classes define how your objects are named, which
 * actions and items they contain and which item type they've got.
 *
 * A word on actions: Each single object of a FrontendLogic subclass has the
 * same amount of actions. You can't have a different set of actions for
 * different objects of the same type.
 *
 * @package    Plugin
 * @subpackage FrontendLogic
 */
abstract class FrontendLogic {

    /**
     * getFriendlyName: Returns the friendly (e.g. display) name of your
     * objects.
     *
     * @return string
     *         Name of the object
     */
    public function getFriendlyName() {
        return "Inherited class *must* override getFriendlyName";
    }

    /**
     * listActions: Lists all actions
     *
     * The returned array has the format $actionname => $actiondescription
     *
     * @return array
     *         Array of all actions
     */
    public function listActions() {
        return array("Inherited class *must* override listActions");
    }

    /**
     * listItems: Lists all available items
     *
     * The returned array has the format $itemid => $itemname
     *
     * @return array
     *         Array of items
     */
    public function listItems() {
        return array("Inherited class *must* override listItems");
    }

}
