<?php

/**
 * This file contains the class for workflow art allocation management.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for workflow art allocation management.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @method WorkflowArtAllocation createNewItem
 * @method WorkflowArtAllocation|bool next
 */
class WorkflowArtAllocations extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cRegistry::getDbTableName('workflow_art_allocation'), "idartallocation");
        $this->_setItemClass("WorkflowArtAllocation");
    }

    /**
     * @param $idartlang
     *
     * @return bool|Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idartlang)
    {
        $idartlang = cSecurity::toInteger($idartlang);

        $sql = "SELECT `idartlang` FROM `%s` WHERE idartlang = %d";
        $this->db->query($sql, cRegistry::getDbTableName('art_lang'), $idartlang);
        if (!$this->db->nextRecord()) {
            $this->lasterror = i18n("Article doesn't exist", "workflow");
            return false;
        }

        $this->select("idartlang = $idartlang");
        if ($this->next() !== false) {
            $this->lasterror = i18n("Article is already assigned to a usersequence step.", "workflow");
            return false;
        }

        $newItem = $this->createNewItem();
        $newItem->setField("idartlang", $idartlang);
        $newItem->store();

        return $newItem;
    }

}

/**
 * Class WorkflowArtAllocation
 * Class for a single workflow allocation item
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright  four for business 2003
 */
class WorkflowArtAllocation extends Item
{

    /**
     * Constructor Function
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cRegistry::getDbTableName('workflow_art_allocation'), "idartallocation");
    }

    /**
     * @return bool|WorkflowItem
     * @throws cDbException|cException
     */
    public function getWorkflowItem()
    {
        $userSequence = new WorkflowUserSequence();
        $userSequence->loadByPrimaryKey($this->values["idusersequence"]);

        return $userSequence->getWorkflowItem();
    }

    /**
     * Returns the current item position
     *
     * @return mixed|false
     * @throws cDbException|cException
     */
    public function currentItemPosition()
    {
        $idworkflowitem = cSecurity::toInteger($this->get("idworkflowitem"));

        $workflowItems = new WorkflowItems();
        $workflowItems->select("idworkflowitem = $idworkflowitem");

        if (($item = $workflowItems->next()) !== false) {
            return $item->get("position");
        } else {
            return false;
        }
    }

    /**
     * Returns the current user position.
     * @return mixed|false
     */
    public function currentUserPosition()
    {
        return $this->get("position");
    }

    /**
     * Override store function to send mails
     *
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function store()
    {
        $mailer = new cMailer();

        if (array_key_exists("idusersequence", $this->modifiedValues)) {
            $userSequence = new WorkflowUserSequence();
            $userSequence->loadByPrimaryKey($this->values["idusersequence"]);

            $email = $userSequence->get("emailnoti");
            $escal = $userSequence->get("escalationnoti");

            if ($email == 1 || $escal == 1) {
                // Grab the required information
                $curEditor = getGroupOrUserName($userSequence->get("iduser"));
                $idartlang = $this->get("idartlang");
                $timeunit = $userSequence->get("timeunit");
                $timelimit = $userSequence->get("timelimit");

                $idart = 0;
                $idcat = 0;
                $title = '';
                $author = '';
                $catName = '';

                $db = cRegistry::getDb();

                $sql = "SELECT `author`, `title`, `idart` FROM `%s` WHERE idartlang = %d";
                $db->query($sql, cRegistry::getDbTableName('art_lang'), $idartlang);
                if ($db->nextRecord()) {
                    $idart = $db->f("idart");
                    $title = $db->f("title");
                    $author = $db->f("author");
                }

                // Extract category
                if ($idart > 0) {
                    $sql = "SELECT `idcat` FROM `%s` WHERE `idart` = %d";
                    $db->query($sql, cRegistry::getDbTableName('cat_art'), $idart);
                    if ($db->nextRecord()) {
                        $idcat = $db->f("idcat");
                    }
                }

                if ($idcat > 0) {
                    $sql = "SELECT `name` FROM `%s` WHERE `idcat` = %d";
                    $db->query($sql, cRegistry::getDbTableName('cat_lang'), $idcat);
                    if ($db->nextRecord()) {
                        $catName = $db->f("name");
                    }
                }

                $starttime = time();

                switch ($timeunit) {
                    case "Seconds":
                        $maxtime = $starttime + $timelimit;
                        break;
                    case "Minutes":
                        $maxtime = $starttime + ($timelimit * 60);
                        break;
                    case "Hours":
                        $maxtime = $starttime + ($timelimit * 3600);
                        break;
                    case "Days":
                        $maxtime = $starttime + ($timelimit * 86400);
                        break;
                    case "Weeks":
                        $maxtime = $starttime + ($timelimit * 604800);
                        break;
                    case "Months":
                        $maxtime = $starttime + ($timelimit * 2678400);
                        break;
                    case "Years":
                        $maxtime = $starttime + ($timelimit * 31536000);
                        break;
                    default:
                        $maxtime = $starttime + $timelimit;
                }

                if ($email == 1) {
                    $email = i18n("Hello %s,\n\n" . "you are assigned as the next editor for the Article %s.\n\n" . "More informations:\n" . "Article: %s\n" . "Category: %s\n" . "Editor: %s\n" . "Author: %s\n" . "Editable from: %s\n" . "Editable to: %s\n");

                    $filledMail = sprintf($email, $curEditor, $title, $title, $catName, $curEditor, $author, date("Y-m-d H:i:s", $starttime), date("Y-m-d H:i:s", $maxtime));
                    $user = new cApiUser();

                    if (isGroup($userSequence->get("iduser"))) {
                        $sql = "SELECT `idgroupuser`, `user_id` FROM `%s` WHERE `group_id` = '%s'";
                        $db->query($sql, cRegistry::getDbTableName('groupmembers'), $userSequence->get("iduser"));
                        while ($db->nextRecord()) {
                            $user->loadByPrimaryKey($db->f("user_id"));
                            $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow notification')), $filledMail);
                        }
                    } else {
                        $user->loadByPrimaryKey($userSequence->get("iduser"));
                        $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow notification')), $filledMail);
                    }
                } else {
                    $email = i18n("Hello %s,\n\n" . "you are assigned as the escalator for the Article %s.\n\n" . "More informations:\n" . "Article: %s\n" . "Category: %s\n" . "Editor: %s\n" . "Author: %s\n" . "Editable from: %s\n" . "Editable to: %s\n");

                    $filledMail = sprintf($email, $curEditor, $title, $title, $catName, $curEditor, $author, date("Y-m-d H:i:s", $starttime), date("Y-m-d H:i:s", $maxtime));

                    $user = new cApiUser();

                    if (isGroup($userSequence->get("iduser"))) {
                        $sql = "SELECT `idgroupuser`, `user_id` FROM `%s` WHERE `group_id` = '%s'";
                        $db->query($sql, cRegistry::getDbTableName('groupmembers'), $userSequence->get("iduser"));
                        while ($db->nextRecord()) {
                            $user->loadByPrimaryKey($db->f("user_id"));
                            $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow escalation')), $filledMail);
                        }
                    } else {
                        $user->loadByPrimaryKey($userSequence->get("iduser"));
                        $mailer->sendMail(NULL, $user->getField("email"), stripslashes(i18n('Workflow escalation')), $filledMail);
                    }
                }
            }
        }

        if (parent::store()) {
            $this->db->query("UPDATE `" . $this->table . "` SET `starttime` = NOW() WHERE `" . $this->getPrimaryKeyName() . "` = '" . $this->get($this->getPrimaryKeyName()) . "'");
            return true;
        } else {
            return false;
        }
    }

}
