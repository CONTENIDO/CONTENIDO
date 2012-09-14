<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for handeling modul templates.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Includes
 * @version 1.5.1
 * @author Rusmir Jusufovic
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

/**
 * Class handels the view, creation, edit, delete of modul templates.
 *
 * @author rusmir.jusufovic
 */
class cModuleTemplateHandler extends cModuleHandler {
    // Form fields
    private $_code;

    private $_file;

    private $_tmp_file;

    private $_area;

    private $_frame;

    private $_status;

    private $_action;

    private $_new;

    private $_delete;

    private $_selectedFile;

    private $_reloadScript;

    private $_page = NULL;

    private $_notification = null;

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

    public function __construct($idmod) {
        parent::__construct($idmod);
        $this->_page = new cGuiPage('mod_template');
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
        $this->_tmp_file = $tmpFile;
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
     * The method decide what action is send from
     * user (form).
     *
     * @throws cException if one of the filenames is not set
     * @return string [new, delete,empty,save,rename, default]
     */
    private function _getAction() {
        if (isset($this->_status)) {

            if (isset($_POST['new_x'])) {
                return 'new';
            }

            if (isset($_POST['delete_x'])) {
                return 'delete';
            }

            if (isset($this->_file) && isset($this->_tmp_file)) {
                if ($this->_file == $this->_tmp_file) {
                    // file ist empty also no file in template
                    // directory
                    if (empty($this->_file)) {
                        return 'empty';
                    } else {
                        return 'save';
                    }
                }

                if ($this->_file != $this->_tmp_file) {
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
        // save the contents of file
        $ret = $this->createModuleFile('template', $this->_file, $this->_code);
        // show message
        if ($ret) {
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Saved changes successfully!'));
        }
        // if user selected other file display it
        if ($this->_hasSelectedFileChanged()) {
            $this->_file = $this->_selectedFile;
            $this->_tmp_file = $this->_selectedFile;
        }
    }

    /**
     * rename a file in template directory
     *
     * @throws cException if rename was not successfull
     * @return void
     */
    private function _rename() {
        if ($this->renameModuleFile('template', $this->_tmp_file, $this->_file) == false) {
            throw new cException(i18n('Rename of the file failed!'));
        } else {
            $this->createModuleFile('template', $this->_file, $this->_code);
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Renamed the template file successfully!'));
            $this->_tmp_file = $this->_file;
        }
    }

    /**
     * Make new file
     */
    private function _new() {
        $fileName = '';
        if ($this->existFile('template', $this->_newFileName . '.' . $this->_templateFileEnding)) {
            $fileName = $this->_newFileName . $this->getRandomCharacters(5) . '.' . $this->_templateFileEnding;
            $this->createModuleFile('template', $fileName, '');
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Created a new template file successfully!'));
        } else {
            $this->createModuleFile('template', $this->_newFileName . '.' . $this->_templateFileEnding, '');
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Created a new template file successfully!'));
            $fileName = $this->_newFileName . '.' . $this->_templateFileEnding;
        }
        // set to new fileName
        $this->_file = $fileName;
        $this->_tmp_file = $fileName;
    }

    /**
     * Delete a file
     */
    private function _delete() {
        $ret = $this->deleteFile('template', $this->_tmp_file);
        if ($ret == true) {
            $this->_notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n('Deleted the template file successfully!'));
        }
        $files = $this->getAllFilesFromDirectory('template');

        if (is_array($files)) {
            if (!key_exists('0', $files)) {
                $this->_file = '';
                $this->_tmp_file = '';
            } else {
                $this->_file = $files[0];
                $this->_tmp_file = $files[0];
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
            $this->_tmp_file = $files[0];
            $this->_file = $files[0];
        } else {
            // template directory is empty
            $this->_file = '';
            $this->_tmp_file = '';
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
     */
    private function _validateHTML($notification) {
        /* Try to validate html */
        if (getEffectiveSetting('layout', 'htmlvalidator', 'true') == 'true' && $this->_code !== '') {
            $v = new cHTMLValidator();
            $v->validate($this->_code);
            $msg = '';

            foreach ($v->missingNodes as $value) {
                $idqualifier = '';

                $attr = array();

                if ($value['name'] != '') {
                    $attr['name'] = "name '" . $value['name'] . "'";
                }

                if ($value['id'] != '') {
                    $attr['id'] = "id '" . $value['id'] . "'";
                }

                $idqualifier = implode(', ', $attr);

                if ($idqualifier != '') {
                    $idqualifier = "($idqualifier)";
                }
                $msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value['tag'], $idqualifier, $value['line'], $value['char']) . '<br />';
            }

            if ($msg != '') {
                $notification->displayNotification('warning', $msg) . '<br />';
            }
        }
    }

    private function _makeFormular($belang) {
        $form = new cGuiTableForm('file_editor');
        $form->setTableid('mod_template');
        $form->addHeader(i18n('Edit file'));

        $form->setVar('area', $this->_area);
        $form->setVar('action', $this->_action);
        $form->setVar('frame', $this->_frame);
        $form->setVar('status', 'send');
        $form->setVar('tmp_file', $this->_tmp_file);
        $form->setVar('idmod', $this->_idmod);
        $form->setVar('file', $this->_file);

        $selectFile = new cHTMLSelectElement('selectedFile');
        // array with all files in template directory
        $filesArray = $this->getAllFilesFromDirectory('template');

        // make options fields
        foreach ($filesArray as $key => $file) {
            $optionField = new cHTMLOptionElement($file, $file);

            // select the current file
            if ($file == $this->_file) {
                $optionField->setAttributes('selected', 'selected');
            }

            $selectFile->addOptionElement($key, $optionField);
        }

        $inputAdd = new cHTMLTextbox('new', i18n('Make new template file'), 60);
        $inputAdd->setAttribute('type', 'image');
        $inputAdd->setClass('addfunction');
        // $inputAdd->setAttribute('alt',i18n('New template file'));

        $inputDelete = new cHTMLTextbox('delete', $this->_file, 60);
        $inputDelete->setAttribute('type', 'image');
        $inputDelete->setClass('deletefunction');
        // $inputDelete->setAttribute('alt',i18n('Delete file'));
        $aDelete = new cHTMLLink('');
        $aDelete->setContent($this->_file);
        $aDelete->setClass('deletefunction');

        $aAdd = new cHTMLLink('');
        $aAdd->setContent(i18n('New template file'));
        $aAdd->setClass('addfunction');

        // $tb_name = new cHTMLLabel($sFilename,'');
        $tb_name = new cHTMLTextbox('file', $this->_file, 60);

        $ta_code = new cHTMLTextarea('code', htmlspecialchars($this->_code), 100, 35, 'code');

        $ta_code->setStyle('font-family: monospace;width: 100%;');

        $ta_code->updateAttributes(array(
            'wrap' => getEffectiveSetting('html_editor', 'wrap', 'off')
        ));
        $form->add(i18n('Action'), $inputDelete->toHTML());
        $form->add(i18n('Action'), $inputAdd->toHTML());
        $form->add(i18n('File'), $selectFile);
        $form->add(i18n('Name'), $tb_name);
        $form->add(i18n('Code'), $ta_code);
        $this->_page->setContent(array(
            $form
        ));

        $oCodeMirror = new CodeMirror('code', 'html', substr(strtolower($belang), 0, 2), true, $this->_cfg);
        $this->_page->addScript($oCodeMirror->renderScript());

        // $this->_page->addScript('reload', $this->_reloadScript);
        $this->_page->render();
    }

    /**
     * Display the form and evaluate the action and excute the action.
     *
     * @param cPermission $perm
     * @param cGuiNotification $notificatioin
     */
    public function display($perm, $notificatioin, $belang) {
        $myAction = $this->_getAction();

        // if the user dont have premissions
        if ($this->_havePremission($perm, $notificatioin, $myAction) === -1) {
            return;
        }

        try {
            switch ($myAction) {
                case 'save':
                    $this->_save();
                    break;
                case 'rename':
                    $this->_rename();
                    break;
                case 'new':
                    $this->_new();
                    break;
                case 'delete':
                    $this->_delete();
                    break;
                default:
                    $this->_default();
                    break;
            }

            $this->_code = $this->getFilesContent('template', '', $this->_file);
            $this->_validateHTML($notificatioin);
            $this->_makeFormular($belang);
        } catch (Exception $e) {
            $this->_page->displayError(i18n($e->getMessage()));
            $this->_page->render();
        }
    }

}

class Contenido_Module_Template_Handler extends cModuleTemplateHandler {
    /** @deprecated [2012-07-24] class was renamed to cModuleTemplateHandler */
    public function __construct($idmod) {
        cDeprecated('Class was renamed to cModuleTemplateHandler.');
        parent::__construct($idmod);
    }

}