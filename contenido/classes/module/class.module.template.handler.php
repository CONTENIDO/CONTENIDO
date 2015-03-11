<?php
/**
 * This file contains the module template handler class.
 * TODO: Rework comments of this class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Rusmir Jusufovic
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

/**
 * Class handels the view, creation, edit, delete of modul templates.
 *
 * @package Core
 * @subpackage Backend
 */
class cModuleTemplateHandler extends cModuleHandler {

    // Form fields
    private $_code;

    private $_file;

    private $_tmpFile;

    private $_area;

    private $_frame;

    private $_status;

    private $_action;

    private $_new;

    private $_delete;

    private $_selectedFile;

    private $_reloadScript;

    private $_page = NULL;

    private $_notification = NULL;

    /**
     * The file end of template files.
     *
     * @var string
     */
    private $_templateFileEnding = 'html';

    /**
     * The name of the new file.
     *
     * @var string
     */
    private $_newFileName = 'newfilename';

    /**
     * Action name for create htmltpl
     *
     * @var string
     */
    private $_actionCreate = 'htmltpl_create';

    /**
     * Action name for edit htmltpl
     *
     * @var string
     */
    private $_actionEdit = 'htmltpl_edit';

    /**
     * Action name for delete htmltpl_edit
     *
     * @var string
     */
    private $_actionDelete = 'htmltpl_delete';

    /**
     * In template we test if we have premission for htmltpl.
     *
     * @var string
     */
    private $_testArea = 'htmltpl';

    public function __construct($idmod, $page) {
        parent::__construct($idmod);
        $this->_page = $page;
        $this->_notification = new cGuiNotification();
    }

    /**
     * Set the new delete from Form.
     * This are set if user had push the delete or new button.
     *
     * @param string $new
     * @param string $delete
     */
    public function setNewDelete($new, $delete) {
        $this->_new = $new;
        $this->_delete = $delete;
    }

    /**
     * Set the code from Form!
     *
     * @param string $code
     */
    public function setCode($code) {
        $this->_code = stripslashes($code);
    }

    /**
     * Set the selected file from Form.
     *
     * @param string $selectedFile
     */
    public function setSelectedFile($selectedFile) {
        $this->_selectedFile = $selectedFile;
    }

    /**
     * Set the file and tmpFile from Form.
     * (get it with $_Request...)
     *
     * @param string $file
     * @param string $tmpFile
     */
    public function setFiles($file, $tmpFile) {
        $this->_file = $file;
        $this->_tmpFile = $tmpFile;
    }

    /**
     * Set the status it can be send or empty ''
     *
     * @param string $status
     */
    public function setStatus($status) {
        $this->_status = $status;
    }

    /**
     * Set $frame and idmod and are.
     *
     * @param int $frame
     * @param int $idmod
     * @param int $area
     */
    public function setFrameIdmodArea($frame, $idmod, $area) {
        $this->_frame = $frame;
        $this->_idmod = $idmod;
        $this->_area = $area;
    }

    /**
     * We have two actions wich could send from form.
     *
     * @param string $action
     */
    public function setAction($action) {
        $this->_action = $action;
    }

    /**
     * Checks write permissions for module template
     *
     * @return $this warning notification
     * @return boolean true
     */
    public function checkWritePermissions() {
        if ($this->moduleWriteable('template') == false && cFileHandler::exists(parent::getModulePath() . $this->_directories['template'])) {
            return $this->_notification->displayNotification(cGuiNotification::LEVEL_WARNING, i18n("You have no write permissions for this module"));
        } else {
            return true;
        }
    }

    /**
     * The method decide what action is send from
     * user (form).
     *
     * @throws cException if one of the filenames is not set
     * @return string [new, delete,empty,save,rename, default]
     */
    private function _getAction() {
        global $newModTpl, $deleteModTpl;

        if (isset($this->_status)) {

            if (isset($newModTpl)) {
                return 'new';
            }

            if (isset($deleteModTpl)) {
                return 'delete';
            }

            if (isset($this->_file) && isset($this->_tmpFile)) {
                if ($this->_file == $this->_tmpFile) {
                    // file ist empty also no file in template
                    // directory
                    if (empty($this->_file)) {
                        return 'empty';
                    } else {
                        return 'save';
                    }
                }

                if ($this->_file != $this->_tmpFile) {
                    return 'rename';
                }
            } else {
                // one of files (file or tmp_file) is not set
                throw new cException(i18n('Field of the file name is empty!'));
            }
        } else {
            return 'default';
        }
    }

    /**
     * Has the selected file changed.
     *
     * @return boolean is the filename changed
     */
    private function _hasSelectedFileChanged() {
        if ($this->_file != $this->_selectedFile) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Save the code in the file
     */
    private function _save() {
        // trigger a smarty cache rebuild for template if changes were saved
        $tpl = cSmartyFrontend::getInstance();
        $tpl->clearCache($this->getTemplatePath($this->_file));

        // save the contents of file
        $ret = $this->createModuleFile('template', $this->_file, $this->_code);
        // show message
        if ($ret) {
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Saved changes successfully!'));
        }
        // if user selected other file display it
        if ($this->_hasSelectedFileChanged()) {
            $this->_file = $this->_selectedFile;
            $this->_tmpFile = $this->_selectedFile;
        }
    }

    /**
     * rename a file in template directory
     *
     * @throws cException if rename was not successfull
     */
    private function _rename() {
        // trigger a smarty cache rebuild for old and new template file name
        $tpl = cSmartyFrontend::getInstance();
        $tpl->clearCache($this->getTemplatePath($this->_tmpFile));
        $tpl->clearCache($this->getTemplatePath($this->_file));

        if ($this->renameModuleFile('template', $this->_tmpFile, $this->_file) == false) {
            throw new cException(i18n('Rename of the file failed!'));
        } else {
            $this->createModuleFile('template', $this->_file, $this->_code);
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Renamed the template file successfully!'));
            $this->_tmpFile = $this->_file;
        }
    }

    /**
     * Make new file
     */
    private function _new() {
        $fileName = $this->_newFileName;
        // if target filename already exists insert few random characters into target filename
        if ($this->existFile('template', $this->_newFileName . '.' . $this->_templateFileEnding)) {
            $fileName = $this->_newFileName . $this->getRandomCharacters(5);
        }
        $this->createModuleFile('template', $fileName . '.' . $this->_templateFileEnding, '');
        $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Created a new template file successfully!'));

        // trigger a smarty cache rebuild for new template file
        $tpl = cSmartyFrontend::getInstance();
        $tpl->clearCache($this->getTemplatePath($fileName));

        // set to new fileName
        $this->_file = $fileName;
        $this->_tmpFile = $fileName;
    }

    /**
     * Delete a file
     */
    private function _delete() {
        // trigger a smarty cache rebuild for template that should be deleted
        $tpl = cSmartyFrontend::getInstance();
        $tpl->clearCache($this->getTemplatePath($this->_tmpFile));

        $ret = $this->deleteFile('template', $this->_tmpFile);
        if ($ret == true) {
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Deleted the template file successfully!'));
        }
        $files = $this->getAllFilesFromDirectory('template');

        if (is_array($files)) {
            if (!array_key_exists('0', $files)) {
                $this->_file = '';
                $this->_tmpFile = '';
            } else {
                $this->_file = $files[0];
                $this->_tmpFile = $files[0];
            }
        }
    }

    /**
     * Default case
     */
    public function _default() {
        $files = $this->getAllFilesFromDirectory('template');

        // one or more templates files are in template direcotry
        if (count($files) > 0) {
            $this->_tmpFile = $files[0];
            $this->_file = $files[0];
        } else {
            // template directory is empty
            $this->_file = '';
            $this->_tmpFile = '';
        }
    }

    /**
     * Have the user premissions for the actions.
     *
     * @param cPermission $perm
     * @param cGuiNotification $notification
     * @param string $action
     *
     * @return int if user dont have permission return -1
     */
    private function _havePremission($perm, $notification, $action) {
        switch ($action) {
            case 'new':
                if (!$perm->have_perm_area_action($this->_testArea, $this->_actionCreate)) {
                    $notification->displayNotification('error', i18n('Permission denied'));
                    return -1;
                }
                break;
            case 'save':
            case 'rename':
                if (!$perm->have_perm_area_action($this->_testArea, $this->_actionEdit)) {
                    $notification->displayNotification('error', i18n('Permission denied'));
                    return -1;
                }
                break;
            case 'delete':
                if (!$perm->have_perm_area_action($this->_testArea, $this->_actionDelete)) {
                    $notification->displayNotification('error', i18n('Permission denied'));
                    return -1;
                }
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * This method test the code if the client setting htmlvalidator
     * is not set to false.
     * @param {cGuiNotification} $notification
     */
    private function _validateHTML($notification) {
        // Try to validate html
        if (getEffectiveSetting('layout', 'htmlvalidator', 'true') == 'true' && $this->_code !== '') {
            $v = new cHTMLValidator();
            $v->validate($this->_code);
            $msg = '';

            foreach ($v->missingNodes as $value) {
                $idQualifier = '';

                $attr = array();

                if ($value['name'] != '') {
                    $attr['name'] = "name '" . $value['name'] . "'";
                }

                if ($value['id'] != '') {
                    $attr['id'] = "id '" . $value['id'] . "'";
                }

                $idQualifier = implode(', ', $attr);

                if ($idQualifier != '') {
                    $idQualifier = "($idQualifier)";
                }
                $msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value['tag'], $idQualifier, $value['line'], $value['char']) . '<br>';
            }

            if ($msg != '') {
                $notification->displayNotification('warning', $msg) . '<br>';
            }
        }
    }

    private function _makeFormular($belang, $readOnly) {
        $fileForm = new cGuiTableForm("file_editor");
        $fileForm->addHeader(i18n('Choose file'));
        $fileForm->setTableid('choose_mod_template_file');
        $fileForm->setVar('area', $this->_area);
        $fileForm->setVar('action', $this->_action);
        $fileForm->setVar('frame', $this->_frame);
        $fileForm->setVar('status', 'send');
        $fileForm->setVar('tmp_file', $this->_tmpFile);
        $fileForm->setVar('idmod', $this->_idmod);
        $fileForm->setVar('file', $this->_file);

        $form = new cGuiTableForm('file_editor');
        $form->setTableid('mod_template');
        $form->addHeader(i18n('Edit file'));
        $form->setVar('area', $this->_area);
        $form->setVar('action', $this->_action);
        $form->setVar('frame', $this->_frame);
        $form->setVar('status', 'send');
        $form->setVar('tmp_file', $this->_tmpFile);
        $form->setVar('idmod', $this->_idmod);
        $form->setVar('file', $this->_file);
        $form->setVar('selectedFile', $this->_file);

        $selectFile = new cHTMLSelectElement('selectedFile');
        $selectFile->setClass("fileChooser");
        // array with all files in template directory
        $filesArray = $this->getAllFilesFromDirectory('template');

        // make options fields
        foreach ($filesArray as $key => $file) {

            // ignore dirs
            if (is_dir($file)) {
                continue;
            }

            $optionField = new cHTMLOptionElement($file, $file);

            // select the current file
            if ($file == $this->_file) {
                $optionField->setAttribute('selected', 'selected');
            }

            $selectFile->addOptionElement($key, $optionField);
        }

        $aDelete = new cHTMLLink('main.php');
        $aDelete->setId("deleteLink");
        $aDelete->setContent(i18n("Delete HTML-template"));
        $aDelete->setClass('deletefunction');
        $aDelete->setCustom("deleteModTpl", "1");
        $aDelete->setCustom('area', $this->_area);
        $aDelete->setCustom('action', $this->_action);
        $aDelete->setCustom('frame', $this->_frame);
        $aDelete->setCustom('status', 'send');
        $aDelete->setCustom('idmod', $this->_idmod);
        $aDelete->setCustom('file', $this->_file);
        $aDelete->setCustom('tmp_file', $this->_tmpFile);

        $aAdd = new cHTMLLink('main.php');
        $aAdd->setContent(i18n('New HTML-template'));
        $aAdd->setClass('addfunction');
        $aAdd->setCustom("newModTpl", "1");
        $aAdd->setCustom('area', $this->_area);
        $aAdd->setCustom('action', $this->_action);
        $aAdd->setCustom('frame', $this->_frame);
        $aAdd->setCustom('status', 'send');
        $aAdd->setCustom('tmp_file', $this->_tmpFile);
        $aAdd->setCustom('idmod', $this->_idmod);
        $aAdd->setCustom('file', $this->_file);

        // $oName = new cHTMLLabel($sFilename, '');
        $oName = new cHTMLTextbox('file', $this->_file, 60);

        $oCode = new cHTMLTextarea('code', conHtmlSpecialChars($this->_code), 100, 35, 'code');

        $oCode->setStyle('font-family: monospace;width: 100%;');

        $oCode->updateAttributes(array(
            'wrap' => getEffectiveSetting('html_editor', 'wrap', 'off')
        ));

        $fileForm->add(i18n('Action'), $aAdd->toHTML());
        // show only if file exists
        if ($this->_file) {
            $fileForm->add(i18n('Action'), $aDelete->toHTML());
            $fileForm->add(i18n('File'), $selectFile);
        }

        if($readOnly) {
            $oName->setDisabled('disabled');
        }

        // add fields only if template file exists
        if ($this->_file) {
            $form->add(i18n('Name'), $oName);
            $form->add(i18n('Code'), $oCode);
        }
        $this->_page->setContent(array(
            $fileForm
        ));
        if ($this->_file) {
            $this->_page->appendContent($form);
        }

        $oCodeMirror = new CodeMirror('code', 'html', substr(strtolower($belang), 0, 2), true, $this->_cfg);
        if($readOnly) {
            $oCodeMirror->setProperty("readOnly", "true");

            $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
        }
        $this->_page->addScript($oCodeMirror->renderScript());

        // $this->_page->addScript('reload', $this->_reloadScript);
    }

    /**
     * Display the form and evaluate the action and excute the action.
     *
     * @param cPermission $perm
     * @param cGuiNotification $notification
     * @param string Backend language (not sure about this...)
     * @param bool render in read only mode
     */
    public function display($perm, $notification, $belang, $readOnly) {
        $myAction = $this->_getAction();

        // if the user doesn't have permissions
        if ($this->_havePremission($perm, $notification, $myAction) === -1) {
            return;
        }

        try {
            switch ($myAction) {
                case 'save':
                    if(!$readOnly) {
                        $this->_save();
                    }
                    break;
                case 'rename':
                    if(!$readOnly) {
                        $this->_rename();
                    }
                    break;
                case 'new':
                    if(!$readOnly) {
                        $this->_new();
                    }
                    break;
                case 'delete':
                    if(!$readOnly) {
                        $this->_delete();
                    }
                    break;
                default:
                    $this->_default();
                    break;
            }

            $this->_code = $this->getFilesContent('template', '', $this->_file);
            $this->_validateHTML($notification);
            $this->_makeFormular($belang, $readOnly);
        } catch (Exception $e) {
            $this->_page->displayError(i18n($e->getMessage()));
        }
    }

}