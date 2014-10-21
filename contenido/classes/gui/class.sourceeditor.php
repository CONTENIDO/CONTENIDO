<?php
/**
* This file contains the generic source editor class. It is used for editing HTML templates, JS files and CSS files
*
* @package Core
* @subpackage GUI
* @version SVN Revision $Rev:$
*
* @author Mischa Holz
* @copyright four for business AG <www.4fb.de>
* @license http://www.contenido.org/license/LIZENZ.txt
* @link http://www.4fb.de
* @link http://www.contenido.org
*/
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Source editor class
 * @package Core
 * @subpackage GUI
 */
class cGuiSourceEditor extends cGuiPage {

    /**
     * Name of the file that is being edited
     * @var string
     */
    protected $filename;

    /**
     * Full path to the file that is being edited
     * @var string
     */
    protected $filepath;

    /**
     * CodeMirror type of the file that is being edited
     * @var string
     */
    protected $filetype;

    /**
     * CodeMirror instance
     * @var object
     */
    protected $codeMirror;

    /**
     * Read-only mode or not
     * @var boolean
     */
    protected $readOnly;

    /**
     * Versioning or not
     * @var boolean
     */
    protected $versioning;

    /**
     * The default constructor. Initializes the class and its parent
     * @param string $filename Name of the edited file
     * @param boolean $versioning Is versioning activated or not. Defaults to true
     * @param string $filetype The type of the file. If ommited the class tries to determine the type from the area
     * @param string $filepath Path to the file. If ommited the class tries to determine the path from the type and the area
     */
    public function __construct($filename, $versioning = true, $filetype = '', $filepath = '') {
        global $cfg, $cfgClient, $client, $perm, $area, $action;

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
        $this->filetype = $filetype;
        $this->filepath = $filepath;

        $this->readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
        if($this->readOnly) {
        	cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
        }

        $this->filename = $filename;

        // include the class and create the codemirror instance
        cInclude('external', 'codemirror/class.codemirror.php');
        $this->codeMirror = new CodeMirror('code', $this->filetype, substr(strtolower($belang), 0, 2), true, $cfg, !$this->readOnly);

        $this->versioning = $versioning;

        // update the edited file by using the super global _REQUEST
        $this->update($_REQUEST);
    }

    /**
     * Updates the file according to the options in the array
     * @param array $req Request array. Usually _REQUEST
     */
    protected function update($req) {
        global $cfg, $cfgClient, $db, $client, $area, $frame, $perm, $action;

        // check permissions
        if (!$perm->have_perm_area_action($area, $action)) {
        	$this->displayCriticalError(i18n('Permission denied'));
        }

        // if read only is activated or no data has been sent, skip the update step
        if( ($this->readOnly || ($req['status'] != 'send')) && $req['delfile'] == '') {
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
            if(cFileHandler::exists($this->filepath . $req['delfile'])) {
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
                cFileHandler::remove($this->filepath . $req['delfile']);

                // remove the file information
                $fileInfos->removeFileInformation(array(
                		'filename' => $req['delfile']
                ));

                // display the information and reload the frame
                $this->displayInfo(i18n('File deleted successfully!'));
                $this->abortRendering();

                $this->reloadFrame('left_bottom', array());
                $this->reloadFrame('right_top', "main.php?area=$area&frame=3");
            }
            return;
        }

        // if the filename is empty, display an empty editor and create a new file
        if(is_dir($this->filepath) && cFileHandler::writeable($this->filepath)) {
            // validate the file name
            if(!cFileHandler::validateFilename($req['file'], false)) {
                $this->displayError(i18n('Not a valid filename!'));
                return;
            }
            // check if the file exists already
            if(cFileHandler::exists($this->filepath . '/' . $req['file'])) {
                $this->displayError(i18n('A file with this name exists already'));
                return;
            }
            // set the variables and create the file. Reload frames
	        $this->filepath = $this->filepath . '/' . $req['file'];
	        $this->filename = $req['file'];

	        cFileHandler::write($this->filepath, '');

	        $this->reloadFrame('left_bottom', array(
	        		'file' => $req['file']
	        ));
	        $this->reloadFrame('right_top', "main.php?area=$area&frame=3&file={$req['file']}");
        }

        // save the old code and the old name
        $oldCode = cFileHandler::read($this->filepath);
        $oldName = $this->filename;

        // load the file information and update the description
        $fileInfos = new cApiFileInformationCollection();
        $fileInfos->select('filename = \'' . $this->filename . '\'');
        $fileInfo = $fileInfos->next();
        $oldDesc = '';
        if($fileInfo == null) {
            // file information does not exist yet. Create the row
        	$fileInfo = $fileInfos->create($dbFileType, $this->filename, $req['description']);
        } else {
        	$oldDesc = $fileInfo->get('description');
        	if($oldDesc != $req['description']) {
        		$fileInfo->set('description', $req['description']);
        	}
        }

        // rename the file
        if($req['file'] != $this->filename) {
            // validate the file name
            if(!cFileHandler::validateFilename($req['file'], false)) {
                $this->displayError(i18n('Not a valid filename!'));
            } else {
                // check if a file with that name exists already
                if(!cFileHandler::exists(dirname($this->filepath) . '/' . $req['file'])) {
                    // rename the file and set the variables accordingly
                    cFileHandler::rename($this->filepath, $req['file']);
                	$this->filepath = dirname($this->filepath) . '/' . $req['file'];
                	$this->filename = $req['file'];

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
        if($this->versioning && $oldCode != $req['code']) {
            $fileInfoArray = $fileInfos->getFileInformation($this->filename, $dbFileType);
            $oVersion = new cVersionFile($fileInfo->get('idsfi'), $fileInfoArray, $req['file'], $dbFileType, $cfg, $cfgClient, $db, $client, $area, $frame, $this->filename);
            // Create new Layout Version in cms/version/css/ folder
            $oVersion->createNewVersion();
        }

        // write the code changes and display an error message or success message
        if(cFileHandler::write($this->filepath, $req['code'])) {
            // store the file information
            $fileInfo->store();
            $this->displayInfo(i18n('Changes saved successfully!'));
        } else {
            $this->displayError(i18n('Couldn\'t save the changes! Check the file system permissions.'));
        }
    }

    /**
     * Renders the page
     * @see cGuiPage::render()
     */
    public function render() {
        global $area, $action, $cfg;

        // load the file information
        $fileInfos = new cApiFileInformationCollection();
        $fileInfos->select('filename = \'' . $this->filename . '\'');
        $fileInfo = $fileInfos->next();
        $desc = '';
        if($fileInfo != null) {
            $desc = $fileInfo->get('description');
        }

        // assign description
        $this->set('s', 'DESCRIPTION', $desc);

        // assign the codemirror script, and other variables
        $this->set('s', 'CODEMIRROR_SCRIPT', $this->codeMirror->renderScript());
        $this->set('s', 'AREA', $area);
        $this->set('s', 'ACTION', $action);
        $this->set('s', 'FILENAME', $this->filename);
        if(cFileHandler::readable($this->filepath) && $this->filename != '') {
            $this->set('s', 'SOURCE', conHtmlentities(cFileHandler::read($this->filepath)));
        } else {
            $this->set('s', 'SOURCE', '');
        }
        if($this->readOnly) {
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
