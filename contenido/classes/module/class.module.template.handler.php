<?php

/**
 * This file contains the module template handler class.
 *
 * @todo refactor documentation
 *
 * @package    Core
 * @subpackage Backend
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

/**
 * Class handels the view, creation, edit, delete of module templates.
 *
 * Note:
 * This class is used in two modes:
 * 1. Translate a single module in the backend area "Style > Module > {moduleName}".
 *    Here it needs a `cGuiPage` instance to display the form.
 * 2. Manage module translations in the backend area "Content > Translations" for all modules.
 *    In this case there is no need for a `cGuiPage` instance, the module handle should provide
 *    only its business logic.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleTemplateHandler extends cModuleHandler
{

    /**
     * @var string Template code
     */
    private $code;

    /**
     * @var string Template file name.
     */
    private $file;

    /**
     * @var string Temporary template file name?
     */
    private $tmpFile;

    /**
     * @var string Backend area.
     */
    private $area;

    /**
     * @var int Backend frame.
     */
    private $frame;

    /**
     * @var string Status, 'send', '', or `null`
     */
    private $status;

    /**
     * @var string Backend action.
     */
    private $action;

    /**
     * @var string
     */
    private $new;

    /**
     * @var string
     */
    private $delete;

    /**
     * @var string Selected template file.
     */
    private $selectedFile;

    /**
     * @var ?cGuiPage
     */
    private $guiPage;

    /**
     * @var cGuiNotification
     */
    private $guiNotification;

    /**
     * The extension of template files.
     *
     * @var string
     */
    private $templateFileExt = 'html';

    /**
     * The name of the new template file.
     *
     * @var string
     */
    private $newTemplateFilename = 'newfilename';

    /**
     * Action name for create a template file
     *
     * @var string
     */
    private $actionCreate = 'htmltpl_create';

    /**
     * Action name for edit a template file
     *
     * @var string
     */
    private $actionEdit = 'htmltpl_edit';

    /**
     * Action name for delete a template file
     *
     * @var string
     */
    private $actionDelete = 'htmltpl_delete';

    /**
     * In the template, we test if we have permission for a template file.
     *
     * @var string
     */
    private $testArea = 'htmltpl';

    /**
     * Constructor to create an instance of this class.
     *
     * @param cApiModule|array|int $module
     *         The module instance or the module recordset array from the
     *         database or the id of the module
     * @param ?cGuiPage $page
     * @throws cException
     */
    public function __construct($module, ?cGuiPage $page = null)
    {
        parent::__construct($module);
        $this->guiPage = $page;
        $this->guiNotification = new cGuiNotification();
    }

    /**
     * Set the new delete from the form.
     * This is set if the user had pushed the delete button or the new button.
     */
    public function setNewDelete(string $new, string $delete)
    {
        $this->new = $new;
        $this->delete = $delete;
    }

    /**
     * Set the code from the form!
     */
    public function setCode(string $code)
    {
        $this->code = stripslashes($code);
    }

    /**
     * Set the selected file from the form.
     */
    public function setSelectedFile(string $selectedFile)
    {
        $this->selectedFile = $selectedFile;
    }

    /**
     * Set the file and tmpFile from the form.
     * (get it with $_Request...)
     */
    public function setFiles(string $file, string $tmpFile)
    {
        $this->file = $file;
        $this->tmpFile = $tmpFile;
    }

    /**
     * Set the status it can be sent or empty ''
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Set $frame and idmod and are.
     */
    public function setFrameIdmodArea(int $frame, int $idmod, string $area)
    {
        $this->frame = $frame;
        $this->moduleId = $idmod;
        $this->area = $area;
    }

    /**
     * We have two actions which could send from form.
     */
    public function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * Sets the write permissions for a module template file
     *
     * @throws cException
     */
    public function checkWritePermissions(): ?bool
    {
        if (
            $this->moduleWriteable('template') === false
            && cFileHandler::exists(parent::getModulePath() . $this->directories['template']) === false
        ) {
            $this->guiNotification->displayNotification(
                cGuiNotification::LEVEL_WARNING,
                sprintf(i18n("You have no write permissions for this module: %s"), parent::getModuleName())
            );
            return null;
        } else {
            return true;
        }
    }

    /**
     * The method decides what action is sent from the user (form).
     *
     * @return string [new, delete, empty, save, rename, default]
     * @throws cException If one of the filenames is not set
     */
    private function getAction(): string
    {
        global $newModTpl, $deleteModTpl;

        if (isset($this->status)) {
            if (isset($newModTpl)) {
                return 'new';
            }

            if (isset($deleteModTpl)) {
                return 'delete';
            }

            if (isset($this->file) && isset($this->tmpFile)) {
                if ($this->file == $this->tmpFile) {
                    // file ist empty also no file in the template directory
                    if (empty($this->file)) {
                        return 'empty';
                    } else {
                        return 'save';
                    }
                }

                if ($this->file != $this->tmpFile) {
                    return 'rename';
                }
            } else {
                // one of the files (file or tmp_file) is not set
                throw new cException(i18n('Field of the file name is empty!'));
            }
        }

        return 'default';
    }

    /**
     * Checks if the selected file has changed.
     */
    private function hasSelectedFileChanged(): bool
    {
        return $this->file != $this->selectedFile;
    }

    /**
     * Save the code in the template file.
     *
     * @throws cException
     */
    private function saveAction()
    {
        // if user selected other file display it
        if ($this->hasSelectedFileChanged()) {
            $this->file = $this->selectedFile;
            $this->tmpFile = $this->selectedFile;
        }

        if (isset($this->code)) {
            // to trigger a smarty cache rebuild for a new template file,
            // you need an installed and active smarty plugin (example client)
            if (class_exists('cSmartyFrontend')) {
                $tpl = cSmartyFrontend::getInstance();
                $tpl->clearCache($this->getTemplatePath($this->file));
            }

            // save the contents of a file
            $success = $this->createModuleFile('template', $this->file, $this->code);
            if ($success) {
                $this->guiNotification->displayNotification(
                    cGuiNotification::LEVEL_OK,
                    i18n('Saved changes successfully!')
                );
            }
        }
    }

    /**
     * Rename a file in the template directory
     *
     * @throws cException if rename was not successful
     */
    private function renameAction()
    {
        // to trigger a smarty cache rebuild for a new template file,
        // you need an installed and active smarty plugin (example client)
        if (class_exists('cSmartyFrontend')) {
            $tpl = cSmartyFrontend::getInstance();
            $tpl->clearCache($this->getTemplatePath($this->tmpFile));
            $tpl->clearCache($this->getTemplatePath($this->file));
        }

        if (!$this->renameModuleFile('template', $this->tmpFile, $this->file)) {
            throw new cException(i18n('Rename of the file failed!'));
        } else {
            $this->createModuleFile('template', $this->file, $this->code);
            $this->guiNotification->displayNotification(
                cGuiNotification::LEVEL_OK,
                i18n('Renamed the template file successfully!')
            );

        }
        $this->tmpFile = cString::replaceDiacritics($this->file);
    }

    /**
     * Create a new template file.
     *
     * @throws cInvalidArgumentException|cException
     */
    private function newAction()
    {
        // if target filename already exists insert few random characters into the target filename
        $fileName = $this->newTemplateFilename . '.' . $this->templateFileExt;
        while ($this->existFile('template', $fileName)) {
            $fileName = $this->newTemplateFilename . $this->getRandomCharacters(5) . '.' . $this->templateFileExt;
        }
        $this->createModuleFile('template', $fileName);
        $this->guiNotification->displayNotification(
            cGuiNotification::LEVEL_OK,
            i18n('Created a new template file successfully!')
        );

        // to trigger a smarty cache rebuild for a new template file,
        // you need an installed and active smarty plugin (example client)
        if (class_exists('cSmartyFrontend')) {
            $tpl = cSmartyFrontend::getInstance();
            $tpl->clearCache($this->getTemplatePath($fileName));
        }

        // set to new fileName
        $this->file = $fileName;
        $this->tmpFile = $fileName;
    }

    /**
     * Delete a file
     *
     * @throws cException
     */
    private function deleteAction()
    {
        // to trigger a smarty cache rebuild for a new template file,
        // you need an installed and active smarty plugin (example client)
        if (class_exists('cSmartyFrontend')) {
            $tpl = cSmartyFrontend::getInstance();
            $tpl->clearCache($this->getTemplatePath($this->tmpFile));
        }

        $success = $this->deleteFile('template', $this->tmpFile);
        if ($success) {
            $this->guiNotification->displayNotification(
                cGuiNotification::LEVEL_OK,
                i18n('Deleted the template file successfully!')
            );
        }

        $this->reSetFiles();
    }

    /**
     * Default case
     */
    public function defaultAction()
    {
        $this->reSetFiles();
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    private function reSetFiles()
    {
        $files = $this->getAllFilesFromDirectory('template');

        // one or more template files are in the template directory
        if (count($files) > 0) {
            $this->tmpFile = $files[0];
            $this->file = $files[0];
        } else {
            // the template directory is empty
            $this->file = '';
            $this->tmpFile = '';
        }
    }

    /**
     * Have the user permissions for the actions.
     *
     * @throws cDbException|cException
     */
    private function havePermission(cPermission $perm, cGuiNotification $notification, string $action): bool
    {
        switch ($action) {
            case 'new':
                if (!$perm->have_perm_area_action($this->testArea, $this->actionCreate)) {
                    $notification->displayNotification('error', i18n('Permission denied'));
                    return false;
                } else {
                    return true;
                }
            case 'save':
            case 'rename':
                if (!$perm->have_perm_area_action($this->testArea, $this->actionEdit)) {
                    $notification->displayNotification('error', i18n('Permission denied'));
                    return false;
                } else {
                    return true;
                }
            case 'delete':
                if (!$perm->have_perm_area_action($this->testArea, $this->actionDelete)) {
                    $notification->displayNotification('error', i18n('Permission denied'));
                    return false;
                } else {
                    return true;
                }
            default:
                return true;
        }
    }

    /**
     * This method tests the code if the client setting htmlvalidator is not set to false.
     *
     * @throws cDbException|cException
     */
    private function validateHtml(cGuiNotification $notification)
    {
        // Try to validate HTML
        if (getEffectiveSetting('layout', 'htmlvalidator', 'true') == 'true' && $this->code !== '') {
            $v = new cHTMLValidator();
            $v->validate($this->code);
            $msg = '';

            foreach ($v->getMissingNodes() as $value) {
                $attr = [];

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
                $msg .= sprintf(
                    i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"),
                    $value['tag'], $idQualifier, $value['line'], $value['char']
                ) . '<br>';
            }

            if ($msg != '') {
                $notification->displayNotification('warning', $msg) . '<br>';
            }
        }
    }

    /**
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function makeForm(string $belang, bool $readOnly)
    {
        $fileForm = new cGuiTableForm('file__chooser');
        $fileForm->addTableClass('mgb10');
        $fileForm->setHeader(i18n('Choose file'));
        $fileForm->setVar('area', $this->area);
        $fileForm->setVar('action', $this->action);
        $fileForm->setVar('frame', $this->frame);
        $fileForm->setVar('status', 'send');
        $fileForm->setVar('tmp_file', conHtmlSpecialChars($this->tmpFile));
        $fileForm->setVar('idmod', $this->moduleId);
        $fileForm->setVar('file', conHtmlSpecialChars($this->file));

        $form = new cGuiTableForm('file_editor');
        $form->setTableID('mod_template');
        $form->setHeader(i18n('Edit file'));
        $form->addTableClass('col_flx_m_50p col_first_100');
        $form->setVar('area', $this->area);
        $form->setVar('action', $this->action);
        $form->setVar('frame', $this->frame);
        $form->setVar('status', 'send');
        $form->setVar('tmp_file', conHtmlSpecialChars($this->tmpFile));
        $form->setVar('idmod', $this->moduleId);
        $form->setVar('file', conHtmlSpecialChars($this->file));
        $form->setVar('selectedFile', cString::replaceDiacritics(conHtmlSpecialChars($this->file)));

        $selectFile = new cHTMLSelectElement('selectedFile');
        $selectFile->setClass('fileChooser');
        // array with all files in the template directory
        $filesArray = $this->getAllFilesFromDirectory('template');

        // make options fields
        foreach ($filesArray as $key => $file) {
            // ignore dirs
            if (is_dir($file)) {
                continue;
            }

            // escape option elements to prevent JS injection into form
            $optionField = new cHTMLOptionElement(conHtmlSpecialChars($file), conHtmlSpecialChars($file));

            // select the current file
            if ($file == cString::replaceDiacritics($this->file)) {
                $optionField->setAttribute('selected', 'selected');
            }

            $selectFile->addOptionElement($key, $optionField);
        }

        $aDelete = new cHTMLLink('main.php');
        $aDelete->setID("deleteLink");
        $aDelete->setContent(i18n("Delete HTML-template"));
        $aDelete->setClass('con_func_button deletefunction');
        $aDelete->setCustom('deleteModTpl', '1');
        $aDelete->setCustom('area', $this->area);
        $aDelete->setCustom('action', $this->actionDelete);
        $aDelete->setCustom('frame', $this->frame);
        $aDelete->setCustom('status', 'send');
        $aDelete->setCustom('idmod', $this->moduleId);
        $aDelete->setCustom('file', urlencode($this->file));
        $aDelete->setCustom('tmp_file', urlencode($this->tmpFile));

        $aAdd = new cHTMLLink('main.php');
        $aAdd->setContent(i18n('New HTML-template'));
        $aAdd->setClass('con_func_button addfunction');
        $aAdd->setCustom('newModTpl', '1');
        $aAdd->setCustom('area', $this->area);
        $aAdd->setCustom('action', $this->actionCreate);
        $aAdd->setCustom('frame', $this->frame);
        $aAdd->setCustom('status', 'send');
        $aAdd->setCustom('tmp_file', urlencode($this->tmpFile));
        $aAdd->setCustom('idmod', $this->moduleId);
        $aAdd->setCustom('file', urlencode($this->file));

        // $oName = new cHTMLLabel($sFilename, '');
        $oName = new cHTMLTextbox('file', cString::replaceDiacritics(conHtmlSpecialChars($this->file)), 60);

        $oCode = new cHTMLTextarea('code', conHtmlSpecialChars($this->code), 100, 35, 'code');
        $oCode->setClass('con_code');
        $oCode->updateAttributes(['wrap' => getEffectiveSetting('html_editor', 'wrap', 'off'),]);

        $fileForm->add(i18n('Action'), $aAdd->toHtml());
        // show only if a file exists
        if ($this->file) {
            $fileForm->add(i18n('Action'), $aDelete->toHtml());
            $fileForm->add(i18n('File'), $selectFile);
        }

        if ($readOnly) {
            $oName->setDisabled(true);
        }

        // add fields only if a template file exists
        if ($this->file) {
            $form->add(i18n('Name'), $oName);
            $form->add(i18n('Code'), $oCode);
        }
        $this->guiPage->setContent([$fileForm]);
        if ($this->file) {
            $this->guiPage->appendContent($form);
        }

        $oCodeMirror = new CodeMirror(
            'code',
            'html',
            cString::getPartOfString(cString::toLowerCase($belang), 0, 2),
            true,
            $this->cfg
        );
        if ($readOnly) {
            $oCodeMirror->setProperty('readOnly', 'true');

            $form->setActionButton(
                'submit',
                cRegistry::getBackendUrl() . 'images/but_ok_off.gif',
                i18n('Overwriting files is disabled'),
                's'
            );
        }
        $this->guiPage->addScript($oCodeMirror->renderScript());
    }

    /**
     * Display the form and evaluate the action and execute the action.
     *
     * @param cPermission $perm
     * @param cGuiNotification $notification
     * @param string $belang Backend language (not sure about this...)
     * @param bool $readOnly render in read-only mode
     * @throws cDbException|cException
     */
    public function display(cPermission $perm, cGuiNotification $notification, string $belang, bool $readOnly)
    {
        // No need to build the form if the gui page is not set!
        // This is the case when the module translations are managed at the backend area "Content > Translations".
        if (!$this->guiPage) {
            return;
        }

        $myAction = $this->getAction();

        // if the user doesn't have permissions
        if (!$this->havePermission($perm, $notification, $myAction)) {
            return;
        }

        try {
            switch ($myAction) {
                case 'save':
                    if (!$readOnly) {
                        $this->saveAction();
                    }
                    break;
                case 'rename':
                    if (!$readOnly) {
                        $this->renameAction();
                    }
                    break;
                case 'new':
                    if (!$readOnly) {
                        $this->newAction();
                    }
                    break;
                case 'delete':
                    if (!$readOnly) {
                        $this->deleteAction();
                    }
                    break;
                default:
                    $this->defaultAction();
                    break;
            }

            $this->code = $this->getFilesContent('template', '', $this->file);
            $this->validateHtml($notification);
            $this->makeForm($belang, $readOnly);
        } catch (Exception $e) {
            $this->guiPage->displayError(i18n($e->getMessage()));
        }
    }

}
