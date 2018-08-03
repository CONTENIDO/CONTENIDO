<?php

/**
 * This file contains the generic source editor class. It is used for editing HTML templates, JS files and CSS files
 *
 * @package Core
 * @subpackage GUI
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Source editor class.
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiSourceEditor extends cGuiPage {

    /**
     * Name of the file that is being edited.
     *
     * @var string
     */
    protected $_filename;

    /**
     * Name of the file that is being edited.
     *
     * This variable is different to $filename only if you rename your
     * file.
     *
     * @var string
     */
    protected $_versionfilename;

    /**
     * Full path to the file that is being edited.
     *
     * @var string
     */
    protected $_filepath;

    /**
     * CodeMirror type of the file that is being edited.
     *
     * @var string
     */
    protected $_filetype;

    /**
     * CodeMirror instance.
     *
     * @var object
     */
    protected $_codeMirror;

    /**
     * Read-only mode or not.
     *
     * @var bool
     */
    protected $_readOnly;

    /**
     * Versioning or not.
     *
     * @var bool
     */
    protected $_versioning;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes the class and its parent.
     *
     * @param string $filename
     *                           Name of the edited file
     * @param bool   $versioning [optional]
     *                           Is versioning activated or not. Defaults to true
     * @param string $filetype   [optional]
     *                           The type of the file. If ommited the class tries to determine
     *                           the type from the area
     * @param string $filepath   [optional]
     *                           Path to the file. If ommited the class tries to determine the
     *                           path from the type and the area
     *
     * @throws Exception
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct($filename, $versioning = true, $filetype = '', $filepath = '') {
        global $belang, $cfgClient;

        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $perm = cRegistry::getPerm();
        $area = cRegistry::getArea();
        $action = cRegistry::getAction();

        // call parent constructor
        parent::__construct("generic_source_editor");

        // check permissions
        if (!$perm->have_perm_area_action($area, $action)) {
            $this->displayCriticalError(i18n('Permission denied'));
        }

        // display empty page if no client is selected
        if (!(int) $client > 0) {
            $this->abortRendering();
        }

        // determine the filetype and path by using the area
        if($filetype == '') {
            switch($_REQUEST['area']) {
                case 'style':
                    $filepath = $cfgClient[$client]['css']['path'] . $filename;
                    $filetype = 'css';
                    break;
                case 'js':
                    $filepath = $cfgClient[$client]['js']['path'] . $filename;
                    $filetype = 'js';
                    break;
                case 'htmltpl':
                    $filepath = $cfgClient[$client]['tpl']['path'] . $filename;
                    $filetype = 'html';
                    break;
            }
        }

        // assign variables
        $this->_filetype = $filetype;
        $this->_filepath = $filepath;

        $this->_readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
        if($this->_readOnly) {
            cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
        }

        $this->_filename = $filename;

        // include the class and create the codemirror instance
        cInclude('external', 'codemirror/class.codemirror.php');
        $this->_codeMirror = new CodeMirror('code', $this->_filetype, cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$this->_readOnly);

        $this->_versioning = $versioning;

        // update the edited file by using the super global _REQUEST
        $this->update($_REQUEST);
    }

    /**
     * Updates the file according to the options in the array.
     *
     * @param array $req
     *         Request array. Usually _REQUEST
     *
     * @throws Exception
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function update($req) {
        global $cfgClient;

        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $db = cRegistry::getDb();
        $frame = cRegistry::getFrame();
        $perm = cRegistry::getPerm();
        $area = cRegistry::getArea();
        $action = cRegistry::getAction();

        // check permissions
        if (!$perm->have_perm_area_action($area, $action)) {
            $this->displayCriticalError(i18n('Permission denied'));
        }

        // if read only is activated or no data has been sent, skip the update step
        if( ($this->_readOnly || ($req['status'] != 'send')) && $req['delfile'] == '') {
            if($req['action'] == '') {
               $this->abortRendering();
            }
            return;
        }

        // if magic quotes are on, strip slashes from the array
        if(ini_get('magic_quotes_gpc')) {
            foreach($req as $key => $value) {
                $req[$key] = stripslashes($value);
            }
        }

        // determine the file type for the file information table
        $dbFileType = '';
        switch($req['area']) {
            case 'style':
                $dbFileType = 'css';
                break;
            case 'js':
                $dbFileType = 'js';
                break;
            case 'htmltpl':
                $dbFileType = 'templates';
                break;
        }

        // delete the specified file
        if($req['delfile'] != '') {
            // check if it exists
            if(cFileHandler::exists($this->_filepath . $req['delfile'])) {
                // load information
                $fileInfos = new cApiFileInformationCollection();
                $fileInfos->select('filename = \'' . $req['delfile'] . '\'');
                $fileInfo = $fileInfos->next();
                // if there is information and if there are versioning files, delete them
                if($fileInfo != null) {
                    $idsfi = $fileInfo->get('idsfi');

                    if (cSecurity::isInteger($idsfi) && is_dir($cfgClient[$client]['version']['path'] . "$dbFileType/$idsfi")) {
                        cDirHandler::recursiveRmdir($cfgClient[$client]['version']['path'] . "$dbFileType/$idsfi");
                    }
                }

                // remove the file
                cFileHandler::remove($this->_filepath . $req['delfile']);

                // remove the file information
                $fileInfos->removeFileInformation(array(
                        'filename' => $req['delfile']
                ));

                // display the information and reload the frame
                $this->displayOk(i18n('File deleted successfully!'));
                $this->abortRendering();

                $this->reloadFrame('left_bottom', array());
                $this->reloadFrame('right_top', "main.php?area=$area&frame=3");
            }
            return;
        }

        // Set version filename
        $this->_versionfilename = $this->_filename;

        // if the filename is empty, display an empty editor and create a new file
        if(is_dir($this->_filepath) && cFileHandler::writeable($this->_filepath)) {
            // validate the file name
            if(!cFileHandler::validateFilename($req['file'], false)) {
                $this->displayError(i18n('Not a valid filename!'));
                return;
            }
            // check if the file exists already
            if(cFileHandler::exists($this->_filepath . '/' . $req['file'])) {
                $this->displayError(i18n('A file with this name exists already'));
                return;
            }
            // set the variables and create the file. Reload frames
            $this->_filepath = $this->_filepath . '/' . $req['file'];
            $this->_filename = $req['file'];

            cFileHandler::write($this->_filepath, '');

            $this->reloadFrame('left_bottom', array(
                    'file' => $req['file']
            ));
            $this->reloadFrame('right_top', "main.php?area=$area&frame=3&file={$req['file']}");
        }

        // save the old code and the old name
        $oldCode = cFileHandler::read($this->_filepath);
        $oldName = $this->_filename;

        // load the file information and update the description
        $fileInfos = new cApiFileInformationCollection();
        $fileInfos->select('filename = \'' . $this->_filename . '\'');
        $fileInfo = $fileInfos->next();
        $oldDesc = '';
        if($fileInfo == null) {
            // file information does not exist yet. Create the row
            $fileInfo = $fileInfos->create($dbFileType, $this->_filename, $req['description']);
        } else {
            $oldDesc = $fileInfo->get('description');
            if($oldDesc != $req['description']) {
                $fileInfo->set('description', $req['description']);
            }
        }

        // rename the file
        if($req['file'] != $this->_filename) {
            // validate the file name
            if(!cFileHandler::validateFilename($req['file'], false)) {
                $this->displayError(i18n('Not a valid filename!'));
            } else {
                // check if a file with that name exists already
                if(!cFileHandler::exists(dirname($this->_filepath) . '/' . $req['file'])) {
                    // rename the file and set the variables accordingly
                    cFileHandler::rename($this->_filepath, $req['file']);
                    $this->_filepath = dirname($this->_filepath) . '/' . $req['file'];
                    $this->_filename = $req['file'];

                    // update the file information
                    $fileInfo->set('filename', $req['file']);

                    // reload frames
                    $this->reloadFrame('left_bottom', array(
                            'file' => $req['file']
                    ));
                    $this->reloadFrame('right_top', "main.php?area=$area&frame=3&file={$req['file']}");
                } else {
                    $this->displayError(i18n('Couldn\'t rename file. Does it exist already?'));
                    return;
                }
            }
        }

        // if the versioning should be updated and the code changed, create a versioning instance and update it
        if($this->_versioning && $oldCode != $req['code']) {
            $fileInfoArray = $fileInfos->getFileInformation($this->_versionfilename, $dbFileType);
            $oVersion = new cVersionFile($fileInfo->get('idsfi'), $fileInfoArray, $req['file'], $dbFileType, $cfg, $cfgClient, $db, $client, $area, $frame, $this->_versionfilename);
            // Create new Layout Version in cms/version/css/ folder
            $oVersion->createNewVersion();
        }

        // write the code changes and display an error message or success message
        if(cFileHandler::write($this->_filepath, $req['code'])) {
            // store the file information
            $fileInfo->store();
            $this->displayOk(i18n('Changes saved successfully!'));
        } else {
            $this->displayError(i18n('Couldn\'t save the changes! Check the file system permissions.'));
        }
    }

    /**
     * Renders the page.
     *
     * @see cGuiPage::render()
     * @param cTemplate|null $template
     * @param bool           $return
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function render($template = NULL, $return = false) {

        $cfg = cRegistry::getConfig();
        $area = cRegistry::getArea();
        $action = cRegistry::getAction();

        // load the file information
        $fileInfos = new cApiFileInformationCollection();
        $fileInfos->select('filename = \'' . $this->_filename . '\'');
        $fileInfo = $fileInfos->next();
        $desc = '';
        if($fileInfo != null) {
            $desc = $fileInfo->get('description');
        }

        // assign description
        $this->set('s', 'DESCRIPTION', $desc);

        // assign the codemirror script, and other variables
        $this->set('s', 'CODEMIRROR_SCRIPT', $this->_codeMirror->renderScript());
        $this->set('s', 'AREA', $area);
        $this->set('s', 'ACTION', $action);
        $this->set('s', 'FILENAME', $this->_filename);
        if(cFileHandler::readable($this->_filepath) && $this->_filename != '') {
            $this->set('s', 'SOURCE', conHtmlentities(cFileHandler::read($this->_filepath)));
        } else {
            $this->set('s', 'SOURCE', '');
        }
        if($this->_readOnly) {
            // if the read only mode is activated, display a greyed out icon
            $this->set('s', 'SAVE_BUTTON_IMAGE', $cfg['path']['images'] . 'but_ok_off.gif');
            $this->set('s', 'SAVE_BUTTON_DESC', i18n('The administratos has disabled edits'));
        } else {
            $this->set('s', 'SAVE_BUTTON_IMAGE', $cfg['path']['images'] . 'but_ok.gif');
            $this->set('s', 'SAVE_BUTTON_DESC', i18n('Save changes'));
        }

        // call the render method of cGuiPage
        parent::render();
    }

}
