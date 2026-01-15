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
 * @extends ItemCollection<WorkflowArtAllocation>
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
        parent::__construct(cDb::getTableName('workflow_art_allocation'), 'idartallocation');
        $this->_setItemClass('WorkflowArtAllocation');
    }

    /**
     * @param $articleLanguageId
     * @return WorkflowArtAllocation|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($articleLanguageId)
    {
        $articleLanguageId = cSecurity::toInteger($articleLanguageId);

        $this->db->query(
            "SELECT `idartlang` FROM `%s` WHERE idartlang = %d",
            cDb::getTableName('art_lang'),
            $articleLanguageId
        );
        if (!$this->db->nextRecord()) {
            $this->lasterror = i18n("Article doesn't exist", "workflow");
            return false;
        }

        $this->select("`idartlang` = $articleLanguageId");
        if ($this->next() !== false) {
            $this->lasterror = i18n("Article is already assigned to a usersequence step.", "workflow");
            return false;
        }

        $newItem = $this->createNewItem();
        $newItem->setField('idartlang', $articleLanguageId);
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
        parent::__construct(cDb::getTableName('workflow_art_allocation'), 'idartallocation');
    }

    /**
     * @return bool|WorkflowItem
     * @throws cDbException|cException
     */
    public function getWorkflowItem()
    {
        $userSequence = new WorkflowUserSequence();
        $userSequence->loadByPrimaryKey($this->values['idusersequence']);

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
        $workflowItemId = cSecurity::toInteger($this->get('idworkflowitem'));

        $workflowItems = new WorkflowItems();
        $workflowItems->select("`idworkflowitem` = $workflowItemId");

        if (($item = $workflowItems->next()) !== false) {
            return $item->get('position');
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
        return $this->get('position');
    }

    /**
     * Override store function to send mails
     *
     * @inheritDoc
     * @throws cException
     */
    public function store()
    {
        $mailer = new cMailer();

        if (array_key_exists('idusersequence', $this->modifiedValues)) {
            $userSequence = new WorkflowUserSequence();
            $userSequence->loadByPrimaryKey($this->values['idusersequence']);

            $email = $userSequence->get('emailnoti');
            $escal = $userSequence->get('escalationnoti');

            if ($email == 1 || $escal == 1) {
                // Grab the required information
                $curEditor = getGroupOrUserName($userSequence->get('iduser'));
                $articleLanguageId = $this->get('idartlang');
                $timeUnit = $userSequence->get('timeunit');
                $timeLimit = $userSequence->get('timelimit');

                $articleId = 0;
                $categoryId = 0;
                $title = '';
                $author = '';
                $catName = '';

                $db = cRegistry::getDb();

                $db->query(
                    "SELECT `author`, `title`, `idart` FROM `%s` WHERE idartlang = %d",
                    cDb::getTableName('art_lang'),
                    $articleLanguageId
                );
                if ($db->nextRecord()) {
                    $articleId = cSecurity::toInteger($db->f('idart'));
                    $title = $db->f('title');
                    $author = $db->f('author');
                }

                // Extract category
                if ($articleId > 0) {
                    $db->query(
                        "SELECT `idcat` FROM `%s` WHERE `idart` = %d",
                        cDb::getTableName('cat_art'),
                        $articleId
                    );
                    if ($db->nextRecord()) {
                        $categoryId = cSecurity::toInteger($db->f('idcat'));
                    }
                }

                if ($categoryId > 0) {
                    $db->query(
                        "SELECT `name` FROM `%s` WHERE `idcat` = %d",
                        cDb::getTableName('cat_lang'),
                        $categoryId
                    );
                    if ($db->nextRecord()) {
                        $catName = $db->f('name');
                    }
                }

                $startTime = time();

                // TODO Code is redundant with contenido/plugins/workflow/cronjobs/advance_workflow.php
                switch ($timeUnit) {
                    case 'Seconds':
                        $maxTime = $startTime + $timeLimit;
                        break;
                    case 'Minutes':
                        $maxTime = $startTime + ($timeLimit * 60);
                        break;
                    case 'Hours':
                        $maxTime = $startTime + ($timeLimit * 3600);
                        break;
                    case 'Days':
                        $maxTime = $startTime + ($timeLimit * 86400);
                        break;
                    case 'Weeks':
                        $maxTime = $startTime + ($timeLimit * 604800);
                        break;
                    case 'Months':
                        $maxTime = $startTime + ($timeLimit * 2678400);
                        break;
                    case 'Years':
                        $maxTime = $startTime + ($timeLimit * 31536000);
                        break;
                    default:
                        $maxTime = $startTime + $timeLimit;
                }

                if ($email == 1) {
                    $filledMail = sprintf(
                        i18n("Hello %s,\n\nyou are assigned as the next editor for the Article %s.\n\nMore informations:\nArticle: %s\nCategory: %s\nEditor: %s\nAuthor: %s\nEditable from: %s\nEditable to: %s\n"),
                        $curEditor,
                        $title,
                        $title,
                        $catName,
                        $curEditor,
                        $author,
                        date('Y-m-d H:i:s', $startTime),
                        date('Y-m-d H:i:s', $maxTime)
                    );
                    $user = new cApiUser();

                    if (isGroup($userSequence->get('iduser'))) {
                        $db->query(
                            "SELECT `idgroupuser`, `user_id` FROM `%s` WHERE `group_id` = '%s'",
                            cDb::getTableName('groupmembers'),
                            $userSequence->get('iduser')
                        );
                        while ($db->nextRecord()) {
                            $user->loadByPrimaryKey($db->f('user_id'));
                            $mailer->sendMail(
                                NULL,
                                $user->getField('email'),
                                stripslashes(i18n('Workflow notification')),
                                $filledMail
                            );
                        }
                    } else {
                        $user->loadByPrimaryKey($userSequence->get('iduser'));
                        $mailer->sendMail(
                            NULL,
                            $user->getField('email'),
                            stripslashes(i18n('Workflow notification')),
                            $filledMail
                        );
                    }
                } else {
                    $filledMail = sprintf(
                        i18n("Hello %s,\n\nyou are assigned as the escalator for the Article %s.\n\nMore informations:\nArticle: %s\nCategory: %s\nEditor: %s\nAuthor: %s\nEditable from: %s\nEditable to: %s\n"),
                        $curEditor,
                        $title,
                        $title,
                        $catName,
                        $curEditor,
                        $author,
                        date('Y-m-d H:i:s', $startTime),
                        date('Y-m-d H:i:s', $maxTime)
                    );

                    $user = new cApiUser();

                    if (isGroup($userSequence->get('iduser'))) {
                        $db->query(
                            "SELECT `idgroupuser`, `user_id` FROM `%s` WHERE `group_id` = '%s'",
                            cDb::getTableName('groupmembers'),
                            $userSequence->get('iduser')
                        );
                        while ($db->nextRecord()) {
                            $user->loadByPrimaryKey($db->f('user_id'));
                            $mailer->sendMail(
                                NULL,
                                $user->getField('email'),
                                stripslashes(i18n('Workflow escalation')),
                                $filledMail
                            );
                        }
                    } else {
                        $user->loadByPrimaryKey($userSequence->get('iduser'));
                        $mailer->sendMail(
                            NULL,
                            $user->getField('email'),
                            stripslashes(i18n('Workflow escalation')),
                            $filledMail
                        );
                    }
                }
            }
        }

        if (parent::store()) {
            $this->db->query(
                "UPDATE `%s` SET `starttime` = NOW() WHERE `%s` = %d",
                $this->table,
                $this->getPrimaryKeyName(),
                $this->get($this->getPrimaryKeyName())
            );
            return true;
        } else {
            return false;
        }
    }

}
